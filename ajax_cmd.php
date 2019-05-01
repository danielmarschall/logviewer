<?php

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

	$res = @mysql_query('select id from vts_fehlerlog where (id = '.$_REQUEST['id'].') '.$hardcoded_filters);
	if (!$res) _err('mysql query failed');
	if (mysql_num_rows($res) == 0) _err('Row not found or no permission given.');

	$res = @mysql_query('update vts_fehlerlog set anzahlsolved = anzahl where (id = '.$_REQUEST['id'].') '.$hardcoded_filters);
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
