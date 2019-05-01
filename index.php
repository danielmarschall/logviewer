<?php

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
	die('<h1>ViaThinkSoft LogViewer - Error</h1><p>'.$e->getMessage().'</p>');
}

# Please keep this code synchronized with ajax_cmd.php
$add_filters = logviewer_additional_filter();
$hardcoded_filters = empty($add_filters) ? '' : "and ($add_filters)";
$hardcoded_filters .= " and (letzter >= DATE_SUB(NOW(),INTERVAL ".MAXYEARS." YEAR))";
# Please keep this code synchronized with ajax_cmd.php

if (isset($_REQUEST['solveall']) && logviewer_allow_solvemark()) {
	mysql_query("update vts_fehlerlog set anzahlsolved = anzahl where text like '%".mysql_real_escape_string($_REQUEST['solveall'])."%'");
	header('location:?sort='.urlencode($sort)); // avoid F5
	die();
}

$filter_add = '';
if (isset($_REQUEST['filter'])) {
	$ary = explode(' ', $_REQUEST['filter']);
	foreach ($ary as $a) {
		$a = trim($a);
		if ($a == '') continue;

		if (substr($a,0,1) == '-') {
			$negate = "NOT ";
			$a = substr($a, 1); // remove "-"
		} else {
			$negate = " ";
		}

		$filter_add .= " and text $negate like '".mysql_real_escape_string('%'.$a.'%')."' ";
	}
}

$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
if ($sort == '') $sort = 'anzahl';
if (($sort != 'anzahl') && ($sort != 'letzter') && ($sort != 'random')) die('Sort falsch');

?>
<html>

<head>
	<title>ViaThinkSoft LogViewer</title>
	<script src="ajax.js"></script>
	<link href="style<?php if (file_exists("style_$hostname.css")) echo "_$hostname"; ?>.css" rel="stylesheet" type="text/css">
</head>

<body>

<h1>ViaThinkSoft LogViewer</h1>

<form method="GET" action="index.php">
<input type="hidden" name="sort" value="<?php echo htmlentities($sort); ?>">
<p>Filter: <input style="width:300px" type="text" name="filter" value="<?php echo htmlentities(isset($_REQUEST['filter']) ? $_REQUEST['filter'] : ''); ?>"> <input type="submit" value="Filter"><?php
if (isset($_REQUEST['filter'])) {
	echo ' <a href="?sort='.htmlentities($sort).'">Clear filter</a>';
	if (logviewer_allow_solvemark()) {
		echo ' | <a href="?sort='.htmlentities($sort).'&solveall='.urlencode($_REQUEST['filter']).'" onclick="return confirm(\'Are you sure?\');">Solve all</a>';
	}
}
?></p>
<p><font size="-3">Search terms divided with whitespace. Prepend hyphen to exclude a search term. Only field "Message" will be searched.</font></p>
</form>

<?php
if (!empty($add_filters)) {
	echo '<span class="filter_hint">Showing max. '.COUNT.' results of max. '.MAXYEARS.' years; Hardcoded filter: '.htmlentities($add_filters).'</span>';
} else {
	echo '<span class="filter_hint">Showing max. '.COUNT.' results of max. '.MAXYEARS.' years</span>';
}
?>

<div id="sort">Sort by: <?php

if ($sort == 'anzahl') {
	echo '<span class="selected_menu">Occurrences</span>';
} else {
	echo '<a href="?sort=anzahl'.((isset($_REQUEST['filter'])) ? '&filter='.urlencode($_REQUEST['filter']) : '').'">Occurrences</a>';
}

?> | <?php

if ($sort == 'letzter') {
	echo '<span class="selected_menu">Last occurrence</span>';
} else {
	echo '<a href="?sort=letzter'.((isset($_REQUEST['filter'])) ? '&filter='.urlencode($_REQUEST['filter']) : '').'">Last occurrence</a>';
}

?> | <?php

if ($sort == 'random') {
	echo '<span class="selected_menu">Random order</span>';
} else {
	echo '<a href="?sort=random'.((isset($_REQUEST['filter'])) ? '&filter='.urlencode($_REQUEST['filter']) : '').'">Random order</a>';
}

?> (<span id="count"><?php

$res = mysql_query("select count(*) as cnt from vts_fehlerlog where (anzahl > anzahlsolved) ".$filter_add." ".$hardcoded_filters.";");
$row = mysql_fetch_array($res);
echo $row['cnt'];

?></span>)</div>

<table border="1" width="100%">
<thead>
<tr>
<?php
if (SOURCE_STYLE == 0) {
	// nothing
} else if (SOURCE_STYLE == 1) {
?>
	<td>Source</td>
	<td>Module</td>
<?php
} else if (SOURCE_STYLE == 2) {
?>
	<td>User</td>
<?php
}
?>
	<?php if (logviewer_allow_solvemark()) { ?><td>Mark&nbsp;as...</td><?php } ?>
	<td>Count</td>
	<td>Last&nbsp;occurrence</td>
	<td>Message</td>
</tr>
</thead>
<tbody>
<tr>
<?php

$odd = true;

if ($sort == 'letzter') {
	$res = mysql_query("select * from vts_fehlerlog where (anzahl > anzahlsolved) ".$filter_add." ".$hardcoded_filters." order by letzter desc, anzahl desc, id asc limit ".COUNT);
} else if ($sort == 'anzahl') {
	$res = mysql_query("select * from vts_fehlerlog where (anzahl > anzahlsolved) ".$filter_add." ".$hardcoded_filters." order by anzahl desc, letzter desc, id asc limit ".COUNT);
} else if ($sort == 'random') {
	$res = mysql_query("select * from vts_fehlerlog where (anzahl > anzahlsolved) ".$filter_add." ".$hardcoded_filters." order by RAND() limit ".COUNT);
}
while ($row = mysql_fetch_array($res)) {
	$text = htmlentities($row['text']);
	$text = preg_replace('@ ([^ ]{2,}) on line@ismU', ' <a href="?sort='.urlencode($sort).'&filter=\1">\1</a> on line', $text); // TODO: urlencode \1
	$text = preg_replace('@(at|in) ([^ ]{2,}):(\d+)@ismU', '\1 <a href="?sort='.urlencode($sort).'&filter=\2">\2</a>:\3', $text); // TODO: urlencode \2

	$anzahl = htmlentities($row['anzahl']);
	if ($row['anzahlsolved'] != 0) $anzahl .= '<br>('.$row['anzahlsolved'].')';

	$class = $odd ? 'tr_odd' : 'tr_even';
	$odd = !$odd;

	echo '<tr id="line'.$row['id'].'" class="'.$class.'">';
	if (SOURCE_STYLE == 0) {
		// nothing
	} else if (SOURCE_STYLE == 1) {
		echo '<td>'.htmlentities($row['logfile']).'</td>';
		echo '<td>'.htmlentities($row['modul']).'</td>';
	} else {
		$user = preg_match('@/home/(.+)/@sU', $row['logfile'], $m) ? $m[1] : ((strpos($row['logfile'], '/root/') === 0) ? 'root' : '');
		echo '<td>'.htmlentities($user).'</td>';
	}
	if (logviewer_allow_solvemark()) echo '<td><a href="javascript:_solve('.$row['id'].')">Solved</a></td>';
	echo '<td>'.$anzahl.'</td>';
	echo '<td>'.htmlentities($row['letzter']).'</td>';
	echo '<td>'.$text.'</td>';
	echo '</tr>';
	flush();
}
?>
</tr>
</tbody>
</table>

</body>

</html>
