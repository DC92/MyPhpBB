/* jshint esversion: 6 */

// DEBUG
if (window.location.hash.substr(1, 1) == '0' && window.location.hash.length > 2) {
	window.addEventListener('error', function(evt) {
		alert(evt.filename + ' ' + evt.lineno + ':' + evt.colno + '\n' + evt.message);
	});
	console.log = function(message) {
		alert(message);
	};
}

// BBCode ajout d'un calendrier
$('.calendrier').each(function(index, elCal) {
	const semaines = $(elCal).attr('data-semaines').split(','),
		nowhere = $('#cal_mois_7');

	for (let week = 0; week < 52; week++) {
		const td = $('<td id="cal_s' + week + '"><span/></td>');
		td.appendTo(nowhere); // Attach it to be able to get it
		if ($('body#phpbb').length) { // posting
			const input = $('<input type="checkbox" value="' + week + '" name="gym_semaines[]" />');
			input.appendTo(td);
			if (semaines.includes(week.toString()))
				input.attr('checked', 'checked');
		} else { // viewtopic
			if (semaines.includes(week.toString()))
				td.addClass('selected');
		}
	}
	displayInputCalendar(elCal);
});

function displayInputCalendar(elCal) {
	// Marque le jour et attribue au mois
	let jour = parseInt(elCal.value || $(elCal).attr('data-jour') || 0, 10);
	if (isNaN(jour)) jour = 0;
	for (let week = 0; week < 52; week++) {
		const wEl = $('#cal_s' + week),
			date = new Date(annee_debut, 8 - 1, 3 + week * 7 + jour); // Jour suivant le lundi suivant le 1er aout annee_debut
		wEl.appendTo('#cal_mois_' + date.getMonth());
		wEl.children().html(date.getDate());
	}

	// Ajoute une case aux mois n'ayant que 4 jours de ce type
	if (!$('body#phpbb').length) // viewtopic
		$('.calendrier tr').each(function() {
			if ($(this).children().length == 5)
				$('<td>').appendTo($(this));
		});

	// Affiche le calendrier si la coche scolaire n'est pas cochée
	const scolaireChecked = $('#gym_scolaire:checked').length;
	$('#edit_semaines').css('display', scolaireChecked ? 'none' : 'block');
}

// BBCode ajout d'une carte
$('.carte').each(function(index, elCarte) {
	if (elCarte.innerText) {
		const ll = ol.proj.transform(eval('[' + elCarte.textContent + ']'), 'EPSG:4326', 'EPSG:3857');
		elCarte.innerHTML = null; // Don't loop

		new ol.Map({
			layers: [
				new ol.layer.Tile({
					source: new ol.source.OSM(),
				}),
				new ol.layer.Vector({
					source: new ol.source.Vector({
						features: [
							new ol.Feature({
								geometry: new ol.geom.Point(ll),
							}),
						]
					}),
					style: new ol.style.Style({
						image: new ol.style.Icon(({
							src: 'ext/Dominique92/Gym/styles/prosilver/theme/images/ballon-rose.png',
							anchor: [0.5, 0.8],
						})),
					}),
				}),
			],
			target: elCarte,
			controls: [], // No zoom
			view: new ol.View({
				center: ll,
				zoom: 17
			})
		});
	}
});

// Slideshow
setInterval(function() {
	$('.slideshow ul').animate({
			marginLeft: -400,
		},
		800,
		function() {
			$(this).css({
				marginLeft: 0,
			}).find('li:last').after($(this).find('li:first'));
		}
	);
}, 5000);

// Sous-menus déroulants ne dépassent pas à droite
function submenuPos() {
	$('.submenu').each(function(index, el) {
		const ww = $(window).width(),
			elw = $(el).width(),
			pp = $(el).parent().position();
		$(el).css({
			top: pp.bottom,
			left: Math.min(pp.left, ww - elw)
		});
	});
}
submenuPos();
$(window).on('resize', submenuPos);