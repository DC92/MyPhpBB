<?php
header('Content-Type: application/javascript');

// Check new version each time the url is called
header('Expires: '.date('r'));
header('Cache-Control: no-cache');
header('Pragma: no-cache');

$tag = 0;
$gpx_files = '';
if (isset ($_GET['gpx'])) {
	foreach (glob ($_GET['gpx'].'*.gpx') as $f) {
		$tag += filesize ($f);
		$gpx_files .= "\n\t\t\t\t'". dirname($_GET['url']) .'/'. basename($f) ."',";
	}
}
// Package files
foreach (glob ('*') as $f)
	$tag += filesize ($f);

// Generate a tag depending on the total concerned files size
$service_worker = '// Version '.$tag.PHP_EOL
	.file_get_contents ('service-worker.js');

// Add gpx files to the list of files to cache
$service_worker = str_replace (
	'addAll([',
	'addAll(['.$gpx_files,
	$service_worker
);

$service_worker = str_replace (
	'index.html',
	$_GET['url'],
	$service_worker
);

$service_worker = str_replace (
	'manifest.json',
	'manifest.json.php?url='.$_GET['url'],
	$service_worker
);

echo $service_worker;