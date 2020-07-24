<!DOCTYPE html>
<html>
<head>
	<!--
	© Dominique Cavailhez 2019
	https://github.com/Dominique92/MyOl
	Based on https://openlayers.org
	-->

	<base href="../assets/MyOl/gps/" />
	<link rel="manifest" href="manifest.json.php?url=<?=$_SERVER['SCRIPT_NAME']?>">

	<title>MyGPS</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" type="image/png" href="favicon.png" />

	<!-- Openlayers -->
	<link href="../ol/ol.css" type="text/css" rel="stylesheet">
	<script src="../ol/ol.js"></script>

	<!-- Recherche par nom -->
	<link href="../geocoder/ol-geocoder.min.css" type="text/css" rel="stylesheet">
	<script src="../geocoder/ol-geocoder.js"></script>

	<!-- My Openlayers -->
	<link href="../myol.css" type="text/css" rel="stylesheet">
	<script src="../myol.js"></script>

	<!-- This app -->
	<script>
		var service_worker = "service-worker.js.php?url=<?=$_SERVER['SCRIPT_NAME']?>" +
				"&gpx=<?=urlencode(str_replace(basename(__FILE__),'',__FILE__))?>",
			keys = {
				ign: 'hcxdz5f1p9emo4i1lch6ennl', // Get your own (free) IGN key at http://professionnels.ign.fr/ign/contrats
				thunderforest: 'ee751f43b3af4614b01d1bce72785369', // Get your own (free) THUNDERFOREST key at https://manage.thunderforest.com
				bing: 'ArLngay7TxiroomF7HLEXCS7kTWexf1_1s1qiF7nbTYs2IkD3XLcUnvSlKbGRZxt' // Get your own (free) BING key at https://www.microsoft.com/en-us/maps/create-a-bing-maps-key
				// SwissTopo : You need to register your domain in https://shop.swisstopo.admin.ch/fr/products/geoservice/swisstopo_geoservices/WMTS_info
			};
	</script>
	<link href="index.css" type="text/css" rel="stylesheet">
	<script src="index.js" defer="defer"></script>
</head>

<body>
	<div id="liste">
		<p>Cliquez sur le nom de la trace pour l'afficher :</p>
		<ul>
		<?php
			foreach (glob ('*.gpx') AS $gpx) { ?>
				<li>
					<a title="Cliquer pour afficher la trace"
						onclick="addLayer('<?=dirname($_SERVER['SCRIPT_NAME']).'/'.$gpx?>')">
						<?=ucfirst(pathinfo($gpx,PATHINFO_FILENAME))?>
					</a>
				</li>
		<?php } ?>
		</ul>
		<p>Puis sur la cible pour afficher votre position.</p>
		<p><a onclick="document.getElementById('liste').style.display='none'">FERMER</a></p>
		<br/>
	</div>

	<div id="map"></div>
</body>
</html>