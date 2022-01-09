<?php

/*
 * ViaThinkSoft LogViewer
 * Copyright 2018-2019 Daniel Marschall, ViaThinkSoft
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

if (!isset($_REQUEST['cmd'])) _err('cmd is missing');

require_once __DIR__.'/config.inc.php';

$hostname = trim(file_get_contents('/etc/hostname'));

if (file_exists(__DIR__."/db_$hostname.inc.php")) {
	require_once __DIR__."/db_$hostname.inc.php";
} else {
	require_once __DIR__.'/db.inc.php';
}

if (file_exists(__DIR__."/auth_$hostname.inc.php")) {
	require_once __DIR__."/auth_$hostname.inc.php";
} else {
	require_once __DIR__.'/auth.inc.php';
}

try {
	logviewer_check_access();
} catch (Exception $e) {
	_err($e->getMessage());
}

# Please keep this code synchronized with index.php
$add_filters = logviewer_additional_filter();
$hardcoded_filters = empty($add_filters) ? '' : "and ($add_filters)";
$hardcoded_filters .= " and (letzter >= DATE_SUB(NOW(),INTERVAL ".MAXYEARS." YEAR))";
# Please keep this code synchronized with index.php

if ($_REQUEST['cmd'] == 'solve') {
	if (!is_numeric($_REQUEST['id'])) _err('Invalid solve id');

	try {
		if (file_exists(__DIR__."/db_$hostname.inc.php")) {
			require_once __DIR__."/db_$hostname.inc.php";
		} else {
			require_once __DIR__.'/db.inc.php';
		}

		if (!logviewer_allow_solvemark()) _err('No permission to mark entries as solved!');
	} catch (Exception $e) {
		_err($e->getMessage());
	}

	$res = @db_query('select id from vts_fehlerlog where (id = '.$_REQUEST['id'].') '.$hardcoded_filters);
	if (!$res) _err('mysql query failed');
	if (db_num_rows($res) == 0) _err('Row not found or no permission given.');

	$res = @db_query('update vts_fehlerlog set anzahlsolved = anzahl where (id = '.$_REQUEST['id'].') '.$hardcoded_filters);
	if (!$res) _err('mysql query failed');

	$out = array();
	$out['success'] = true;
	$out['id'] = $_REQUEST['id']; // give OK to hide the line
	header('Content-Type: application/json');
	die(json_encode($out));
} else {
	_err('unknown cmd');
}

# ---

function _err($msg) {
	$out = array();
	$out['success'] = false;
	$out['error'] = $msg;
	header('Content-Type: application/json');
	die(json_encode($out));
}
