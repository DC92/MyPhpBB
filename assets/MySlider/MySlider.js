/**
 * © Dominique Cavailhez 2020
 * https://github.com/Dominique92
 *
 * Provide a minimal nice slideshow
 * Local or remote images
 * Key navigation
 * Validated on Chrome, FF, Edge & Opera on Win10 & Android;
 * Safari on IOS; Brave & IE11 on Win10
 */

function mySlider(id, slides, options) {
	options = options || {};

	const sliderJq = $('#' + id),
		divSlides = [],
		divThumbs = [],
		// Options
		scrollDelay = options.scrollDelay || 5000, // Milliseconds
		showButtonsDelay = options.showButtonsDelay || 1500, // Milliseconds
		nextTitle = options.nextTitle || "Suivant",
		previousTitle = options.previousTitle || "Précédent",
		playTitle = options.playTitle || "Défilement",
		stopTitle = options.stopTitle || "Pause",
		downloadTitle = options.downloadTitle || "Télécharger l'image courante",
		fullscreenTitle = options.fullscreenTitle || "Plein écran",
		goToTitle = options.goToTitle || "Voir";
	let currentSlideNb = -1,
		scrollTimer,
		showButtonsTimer;

	// Buttons previous / next / play / stop
	sliderJq
		.addClass('myslider')
		.append($('<p class="myslider-thumbs">'))
		.append($('<p class="myslider-comment">'))
		.append(
			$('<a class="myslider-next">')
			.attr('title', nextTitle)
			.click(function() {
				displaySlide(currentSlideNb + 1);
			}))
		.append(
			$('<a class="myslider-previous">')
			.attr('title', previousTitle)
			.click(function() {
				displaySlide(currentSlideNb - 1);
			}))
		.append(
			$('<a class="myslider-play">')
			.attr('title', playTitle)
			.click(switchScroll))
		.append(
			$('<a class="myslider-stop">')
			.attr('title', stopTitle)
			.click(switchScroll)
		)
		.append(
			$('<a class="myslider-download">')
			.attr('title', downloadTitle)
		)
		.on('mousemove', showButtons);

	// Full screen
	const sliderFullScreen =
		sliderJq[0].webkitRequestFullScreen || // Chrome, Opera Win10 & Android, Brave, Edge Win10 & Android
		sliderJq[0].mozRequestFullScreen || // FF Win10 & Android
		sliderJq[0].msRequestFullscreen; // IE11
	const sliderExitFullScreen =
		document.webkitExitFullscreen ||
		document.mozCancelFullScreen ||
		document.msExitFullscreen;

	if (sliderFullScreen)
		sliderJq.append(
			$('<a class="myslider-fullscreen">')
			.attr('title', fullscreenTitle)
			.click(function() {
				// Normal window
				if (window.screenTop || window.innerHeight != screen.height)
					sliderFullScreen.call(sliderJq[0]);
				// Full screen
				else
					sliderExitFullScreen.call(document);
			})
		);

	document.addEventListener('keydown', function(evt) {
		switch (evt.keyCode) {
			case 33: // Page up
			case 37: // >
			case 38: // Up
				displaySlide(currentSlideNb - 1);
				break;
			case 34: // Page down
			case 39: // <
			case 40: // Down
				displaySlide(currentSlideNb + 1);
				break;
			case 35: // End
				displaySlide(slides.length - 1);
				break;
			case 36: // Begin
				displaySlide(0);
				break;
			case 13: // Enter
			case 32: // Space
				switchScroll();
		}
	});

	// Start slide show
	showButtons();
	loadimg(0);
	switchScroll();

	function loadimg(i) {
		// Preload slides
		$('<img>').attr({
				src: slides[i][0]
			})
			.on('load', function() { // Loop until the end of the image list
				if (i + 1 < slides.length)
					loadimg(i + 1);
			});

		// Create the display element
		divSlides[i] = $('<div>').css('backgroundImage', 'url("' + slides[i][0] + '")');

		// Add the thumbnail to the thumbs
		divThumbs[i] = $('<a>')
			.css('backgroundImage', 'url("' + slides[i][0] + '")')
			.attr({
				title: goToTitle + ' ' + slides[i][1]
			})
			.on('click', function() {
				displaySlide(i);
			});
		$('.myslider-thumbs').append(divThumbs[i]);
	}

	function displaySlide(i) {
		if (i === undefined)
			i = currentSlideNb + 1;
		if (0 > i || i >= slides.length)
			i = 0;
		currentSlideNb = i;

		// Reset show-play if any
		if (scrollTimer) {
			clearInterval(scrollTimer);
			scrollTimer = setInterval(displaySlide, scrollDelay);
		}

		// Insert the new divSlides
		sliderJq.append(divSlides[i]);
		$('.myslider-comment').html(slides[i].length > 1 ? slides[i][1] : '');

		// Highlight the displayed thumbnail
		for (let j = 0; j < divThumbs.length; j++)
			divThumbs[j][i == j ? 'addClass' : 'removeClass']('highlighted');

		$('.myslider-download').attr('href', slides[i][0]).attr('download', 'diapo_' + i + '.jpg').attr('target', '_blank');

		// Hide frist previous & last next buttons
		$('.myslider-previous').css('display', i > 0 ? 'block' : 'none'); //style. = ;
		$('.myslider-next').css('display', i < slides.length - 1 ? 'block' : 'none'); //style. = ;
	}

	function switchScroll() {
		if (!scrollTimer) {
			// It was stop
			displaySlide();
			scrollTimer = setInterval(displaySlide, scrollDelay);
			sliderJq.addClass('show-play');
		} else {
			// It was play
			clearInterval(scrollTimer);
			scrollTimer = 0;
			sliderJq.removeClass('show-play');
		}
	}

	function showButtons() {
		sliderJq.addClass('show-buttons');

		if (showButtonsTimer)
			clearTimeout(showButtonsTimer);

		showButtonsTimer = setTimeout(function() {
			sliderJq.removeClass('show-buttons');
		}, showButtonsDelay);
	}
}