// The first time a user hits the page an install event is triggered.
// The other times an update is provided if the remote service-worker source md5 is different
self.addEventListener('install', function(e) {
	caches.delete('myolCache');
	e.waitUntil(
		caches.open('myolCache').then(function(cache) {
			return cache.addAll([
				'index.html',
				'index.css',
				'index.js',
				'favicon.png',
				'manifest.json',
				'../ol/ol.css',
				'../ol/ol.js',
				'../geocoder/ol-geocoder.min.css',
				'../geocoder/ol-geocoder.js',
				'../myol.css',
				'../myol.js',
			]);
		})
	);
});

// Performed each time an URL is required before access to the internet
// Provides cached app file if any available
self.addEventListener('fetch', function(e) {
	e.respondWith(
		caches.match(e.request).then(function(response) {
			return response || fetch(e.request);
		})
	);
});