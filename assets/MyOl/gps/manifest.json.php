<?php
header('Content-Type: application/json');

$manifest = file_get_contents ('manifest.json');

if (isset ($_GET['url']))
	$manifest = str_replace ('index.html', $_GET['url'], $manifest);

echo $manifest;