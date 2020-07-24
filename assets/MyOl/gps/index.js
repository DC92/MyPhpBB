// Force https to allow web apps and geolocation
if (window.location.protocol == 'http:' && window.location.host != 'localhost')
	window.location.href = window.location.href.replace('http:', 'https:');

// Load service worker for web application install & updates
if ('serviceWorker' in navigator)
	navigator.serviceWorker.register(service_worker)
	// Reload if any app file has been updated
	.then(function(reg) {
		reg.addEventListener('updatefound', function() {
			location.reload();
		});
	});

// Openlayers part
// Initialise Openlayers vars
const help = 'Pour utiliser les cartes et le GPS hors réseau :\n' +
	'- Installez l‘application web : explorateur -> options -> ajouter à l‘écran d‘accueil\n' +
	'- Choisissez une couche de carte\n' +
	'- Placez-vous au point de départ de votre randonnée\n' +
	'- Zoomez au niveau le plus détaillé que vous voulez mémoriser\n' +
	'- Passez en mode plein écran (mémorise également les échèles supérieures)\n' +
	'- Déplacez-vous suivant le trajet de votre randonnée suffisamment lentement pour charger toutes les dalles\n' +
	'- Recommencez avec les couches de cartes que vous voulez mémoriser\n' +
	'- Allez sur le terrain et cliquez sur l‘icône "MyGPS"\n' +
	'- Si vous avez un fichier .gpx dans votre mobile, visualisez-le en cliquant sur ▲\n' +
	'* Toutes les dalles visualisées une fois seront conservées dans le cache de l‘explorateur quelques jours\n' +
	'* Cette application ne permet pas d‘enregistrer le parcours\n' +
	'* Fonctionne bien sur Android avec Chrome, Edge & Samsung Internet, un peu moins bien avec Firefox & Safari\n' +
	'* Aucune donnée ni géolocalisation n‘est remontée ni mémorisée',

	baseLayers = {
		'OpenTopo': layerOsmOpenTopo(),
		'OSM outdoors': layerThunderforest(keys.thunderforest, 'outdoors'),
		'OSM transport': layerThunderforest(keys.thunderforest, 'transport'),
		'OSM fr': layerOsm('//{a-c}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png'),
		'IGN': layerIGN(keys.ign, 'GEOGRAPHICALGRIDSYSTEMS.MAPS'),
		'IGN Express': layerIGN(keys.ign, 'GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.CLASSIQUE'),
		'Photo IGN': layerIGN(keys.ign, 'ORTHOIMAGERY.ORTHOPHOTOS'),
		'Cadastre': layerIGN(keys.ign, 'CADASTRALPARCELS.PARCELS', 'png'),
		'SwissTopo': layerSwissTopo('ch.swisstopo.pixelkarte-farbe'),
		'Espagne': layerSpain('mapa-raster', 'MTN'),
		'Angleterre': layerOS(keys.bing),
		'Bing': layerBing(keys.bing, 'Road'),
		'Photo Bing': layerBing(keys.bing, 'Aerial'),
		'Google': layerGoogle('m'),
		'Photo Google': layerGoogle('s'),
	},

	controls = [
		controlLayersSwitcher({
			baseLayers: baseLayers,
		}),
		controlPermalink({
			visible: false,
		}),
		new ol.control.ScaleLine(),
		new ol.control.Attribution({
			collapseLabel: '>',
		}),
		controlMousePosition(),
		new ol.control.Zoom(),
		new ol.control.FullScreen({
			label: '', //HACK Bad presentation on IE & FF
			tipLabel: 'Plein écran',
		}),
		controlGeocoder(),
		controlGPS(),
		controlLoadGPX(),
		controlButton({
			label: '?',
			title: help,
			activate: function() {
				alert(this.title);
			},
		}),
	];

var map = new ol.Map({
	target: 'map',
	controls: controls,
});

function addLayer(url) {
	const layer = layerVectorURL({
		url: url,
		format: new ol.format.GPX(),
		readFeatures: function(response) {
			map.getView().setZoom(1); // Enable gpx rendering anywhere we are
			return (response); // No jSon syntax verification because it's XML
		},
		styleOptions: function() {
			return {
				stroke: new ol.style.Stroke({
					color: 'blue',
					width: 3,
				}),
			};
		},
	});

	// Zoom the map on the added features
	layer.once('prerender', function() {
		const features = layer.getSource().getFeatures(),
			extent = ol.extent.createEmpty();
		for (let f in features)
			ol.extent.extend(extent, features[f].getGeometry().getExtent());
		map.getView().fit(extent, {
			maxZoom: 17,
		});

		if (features.length)
			document.getElementById('liste').style.display = 'none';
	});

	map.addLayer(layer);
}