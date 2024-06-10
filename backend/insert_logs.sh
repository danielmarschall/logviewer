#!/usr/bin/php
<?php

/*
 * ViaThinkSoft LogViewer
 * Copyright 2018-2024 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (php_sapi_name() !== 'cli') {
	die("Error: This script can only be called from command line.\n");
}

$hostname = trim(file_get_contents('/etc/hostname'));

if (file_exists(__DIR__."/../config_$hostname.inc.php")) {
	require_once __DIR__."/../config_$hostname.inc.php";
} else {
	require_once __DIR__.'/../config.inc.php';
}

if (file_exists(__DIR__."/../db_$hostname.inc.php")) {
	require_once __DIR__."/../db_$hostname.inc.php";
} else {
	require_once __DIR__.'/../db.inc.php';
}

$files = array();
foreach (apache_log_locations as $tpl) $files = array_merge($files, glob($tpl));
usort($files, function($a,$b) { return filemtime($a) - filemtime($b); });

$phpfiles = array();
foreach (php_log_locations as $tpl) $phpfiles = array_merge($phpfiles, glob($tpl));
usort($phpfiles, function($a,$b) { return filemtime($a) - filemtime($b); });

$file_nr = 0;
$file_max = count($files) + count($phpfiles);

$TMP_FILE = __DIR__ . '/.insertlogs.cache';
if (file_exists($TMP_FILE)) {
	$cont = file_get_contents($TMP_FILE);
	$cache = unserialize($cont);
} else {
	$cache = array();
}

// Apache Log Files

foreach ($files as $file) {
	$file_nr++;

	if (isset($cache[$file]) && ($cache[$file] == md5_file($file))) continue;

	if (time()-filemtime($file) > MAX_DAYS_LOGFILE * 3600) continue;

	if (substr($file,-3,3) === '.gz') {
		if (IGNORE_GZ) continue;
		$cont = file_get_contents($file);
		$cont = gzdecode($cont);
		if ($cont === false) continue;
		$lines = explode("\n", $cont);
	} else {
		$lines = file($file);
	}

	$line_no = 0;
	$line_max = count($lines);
	$logfile = removeLogrotateSuffix($file);
	foreach ($lines as $line) {
		$line_no++;
		$line = trim($line);

		if (preg_match('@^\[(.*)\] \[(.*)\] \[(.*)\] \[(.*)\] (.*)$@ismU', $line, $m)) {
			// [Sun Aug 13 15:54:16.054530 2017] [fcgid:warn] [pid 28401] [client 104.236.113.44:52188] mod_fcgid: stderr: PHP Notice:  Undefined offset: 11 in /home/d
			$time = $m[1];
			$modul = $m[2];
			$text = $m[5];

			$time = trim(substr($time, 4, 6)).' '.substr($time, -4).' '.substr($time, 11, 8);
			$time_mysql = date('Y-m-d H:i:s', strtotime($time));
		} else if (preg_match('@^(.+)\|(.+)\|(.+)\|(.+)$@ismU', $line, $m)) {
			// 5.6 | /daten/homes/daniel-marschall/hosts/dracenmarx/public_html/wiki/vendor/oyejorge/less.php/lib:91            | ini              | Ini mbstring.internal_encoding is deprecated.
			// A special implementation of PHP codefixer (showing the full path) . TODO: release
			$time = filemtime($file);
			$modul = 'php_codefixer';
			$text = 'PHP Codefixer: ' . trim($m[4]) . ' in ' . trim($m[2]);

			$time_mysql = date('Y-m-d H:i:s', $time);
		} else {
			continue;
		}

		if (strlen($modul) > 30) {
			echo "Attention: Truncate modul: $modul\n";
			$modul = substr($modul, 0, 512);
		}

		// Nicht druckbare Zeichen entf. Insbesondere wichtig wegen folgendem SQL Fehler, wenn z.B. jemand Steuerzeichen bei einem Angriff in die Logs einfließen lässt
		// "Illegal mix of collations (latin1_swedish_ci,IMPLICIT) and (utf8mb4_general_ci,COERCIBLE) for operation '='"
		$text = preg_replace('/[[:^print:]]/', '', $text);

		if (strlen($text) > 512) {
			echo "Attention: Truncate text in file $file: $text\n";
			$text = substr($text, 0, 512);
		}

		$res = mysql_query("select * from vts_fehlerlog where modul = '".mysql_real_escape_string($modul)."' and logfile = '".mysql_real_escape_string($logfile)."' and text = '".mysql_real_escape_string($text)."';");
		#echo mysql_error();
		if (mysql_num_rows($res) > 0) {
			mysql_query("update vts_fehlerlog set anzahl = anzahl + 1, letzter = '$time_mysql' " .
			            "where modul = '".mysql_real_escape_string($modul)."' and logfile = '".mysql_real_escape_string($logfile)."' and text = '".mysql_real_escape_string($text)."' and letzter < '".$time_mysql."';");
			#echo mysql_error();

		} else {
			mysql_query("insert into vts_fehlerlog (modul, text, anzahl, letzter, logfile) " .
			            "values ('".mysql_real_escape_string($modul)."', '".mysql_real_escape_string($text)."', 1, '".$time_mysql."', '".mysql_real_escape_string($logfile)."');");
			#echo mysql_error();
		}
		echo "file $file_nr/$file_max (line $line_no/$line_max)                     \r";
	}

	$cache[$file] = md5_file($file);
}

// PHP Log files

foreach ($phpfiles as $file) {
	$file_nr++;

	if (isset($cache[$file]) && ($cache[$file] == md5_file($file))) continue;

	if (time()-filemtime($file) > MAX_DAYS_LOGFILE * 3600) continue;

	if (substr($file,-3,3) === '.gz') {
		if (IGNORE_GZ) continue;
		$cont = file_get_contents($file);
		$cont = gzdecode($cont);
		if ($cont === false) continue;
	} else {
		$cont = file_get_contents($file);
	}
	$cont = str_replace("\r", "", $cont);
	$cont = str_replace("\n ", " ", $cont);
	$lines = explode("\n", $cont);

	$line_no = 0;
	$line_max = count($lines);
	$logfile = removeLogrotateSuffix($file);
	foreach ($lines as $line) {
		$line_no++;
		$line = trim($line);

		echo "file $file_nr/$file_max (line $line_no/$line_max)                     \r";

		if (preg_match('@^\[(.*)\] ((.*)(\n ){0,1})$@ismU', $line, $m)) {
			# [19-Aug-2017 23:00:54 europe/berlin] PHP Notice:  Undefined variable: ssl in /home/viathinksoft/public_html/serverinfo/index.php on line 364
			$time = $m[1];
			$modul = '';
			$text = $m[2];

			$time = trim(substr($time, 0, 20));
			$time_mysql = date('Y-m-d H:i:s', strtotime($time));
		} else {
			continue;
		}

		if (strpos($text, '{"reqId":"') !== false) {
			// For some reason, owncloud or nextcloud seems to write to php_error.log and not in data/nextcloud.log ?? But only sometimes ??
			// TODO: Should we try to parse this JSON log message?

			// [12-Sep-2023 15:01:24 UTC] {"reqId":"f2uD4QSS9xIjAAWgbeVb","level":3,"time":"2023-09-12T15:01:24+00:00","remoteAddr":"1.2.3.4","user":"--","app":"core","method":"GET","url":"/index.php/settings/admin/overview","message":"Permission denied","userAgent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36","version":"27.0.2.1","exception":{"Exception":"RedisException","Message":"Permission denied","Code":0,"Trace":[{"file":"/daten/homes/owncloud/public_html/lib/private/RedisFactory.php","line":137,"function":"pconnect","class":"Redis","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/private/RedisFactory.php","line":178,"function":"create","class":"OC\\RedisFactory","type":"->","args":["*** sensitive parameters replaced ***"]},{"file":"/daten/homes/owncloud/public_html/lib/private/Memcache/Redis.php","line":66,"function":"getInstance","class":"OC\\RedisFactory","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/private/Memcache/Redis.php","line":72,"function":"getCache","class":"OC\\Memcache\\Redis","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/private/App/InfoParser.php","line":58,"function":"get","class":"OC\\Memcache\\Redis","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/private/App/AppManager.php","line":732,"function":"parse","class":"OC\\App\\InfoParser","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/private/legacy/OC_App.php","line":434,"function":"getAppInfo","class":"OC\\App\\AppManager","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/private/AppFramework/App.php","line":71,"function":"getAppInfo","class":"OC_App","type":"::"},{"file":"/daten/homes/owncloud/public_html/lib/private/legacy/OC_App.php","line":155,"function":"buildAppNamespace","class":"OC\\AppFramework\\App","type":"::"},{"file":"/daten/homes/owncloud/public_html/lib/private/AppFramework/Bootstrap/Coordinator.php","line":119,"function":"registerAutoloading","class":"OC_App","type":"::"},{"file":"/daten/homes/owncloud/public_html/lib/private/AppFramework/Bootstrap/Coordinator.php","line":90,"function":"registerApps","class":"OC\\AppFramework\\Bootstrap\\Coordinator","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/base.php","line":703,"function":"runInitialRegistration","class":"OC\\AppFramework\\Bootstrap\\Coordinator","type":"->"},{"file":"/daten/homes/owncloud/public_html/lib/base.php","line":1180,"function":"init","class":"OC","type":"::"},{"file":"/daten/homes/owncloud/public_html/index.php","line":34,"args":["/daten/homes/owncloud/public_html/lib/base.php"],"function":"require_once"}],"File":"/daten/homes/owncloud/public_html/lib/private/RedisFactory.php","Line":137,"CustomMessage":"--"}}

			continue;
		}

		if (strlen($modul) > 30) {
			echo "Attention: Truncate modul: $modul\n";
			$modul = substr($modul, 0, 512);
		}

		// Nicht druckbare Zeichen entf. Insbesondere wichtig wegen folgendem SQL Fehler, wenn z.B. jemand Steuerzeichen bei einem Angriff in die Logs einfließen lässt
		// "Illegal mix of collations (latin1_swedish_ci,IMPLICIT) and (utf8mb4_general_ci,COERCIBLE) for operation '='"
		$text = preg_replace('/[[:^print:]]/', '', $text);

		if (strlen($text) > 512) {
			echo "Attention: Truncate text in file $file: $text\n";
			$text = substr($text, 0, 512);
		}

		$res = mysql_query("select * from vts_fehlerlog where modul = '".mysql_real_escape_string($modul)."' and logfile = '".mysql_real_escape_string($logfile)."' and text = '".mysql_real_escape_string($text)."';");
		#echo mysql_error();
		if (mysql_num_rows($res) > 0) {
			mysql_query("update vts_fehlerlog set anzahl = anzahl + 1, letzter = '$time_mysql' " .
			            "where modul = '".mysql_real_escape_string($modul)."' and logfile = '".mysql_real_escape_string($logfile)."' and text = '".mysql_real_escape_string($text)."' and letzter < '".$time_mysql."';");
			#echo mysql_error();

		} else {
			mysql_query("insert into vts_fehlerlog (modul, text, anzahl, letzter, logfile) " .
			            "values ('".mysql_real_escape_string($modul)."', '".mysql_real_escape_string($text)."', 1, '".$time_mysql."', '".mysql_real_escape_string($logfile)."');");
			#echo mysql_error();
		}
	}

	$cache[$file] = md5_file($file);
}
echo "\n";

file_put_contents($TMP_FILE, serialize($cache));

// Prune old logs

mysql_query('delete from vts_fehlerlog where letzter < date_sub(now(),interval 3 year)');

# ---

function removeLogrotateSuffix($filename) {
	$filename = preg_replace('@\\.(\\d+)(\\.gz){0,1}$@ismU', '', $filename);
	return $filename;
}

