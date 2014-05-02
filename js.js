"use strict";

/* ------------------------------------------------------------------- *|
 * Google Maps Wrapper (Nathan Woods: April 28, 2014)
 * docs: https://developers.google.com/maps/documentation/javascript/reference
 * ------------------------------------------------------------------- */

// Constructor
var ELA_MAP = function (ele, opt, name, txt) {
	this.ele = ele;
	this.opt = opt;
	this.name = name;
	this.txt = txt;
	ELA_MAP._instances.push(this);
	this.init();
};

// Global Object Attributes
ELA_MAP._instances  = [];

// Global Object functions
ELA_MAP.google_maps_loaded = function() {
	for (var i = 0; i < ELA_MAP._instances.length; i++)
		ELA_MAP._instances[i].init();
};
ELA_MAP.load_google = function() {
	if (ELA_MAP.map_src) return;
	ELA_MAP.map_src  = document.createElement('script');
	ELA_MAP.map_src.type = 'text/javascript';
	ELA_MAP.map_src.src  = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&callback=ELA_MAP.google_maps_loaded&key=AIzaSyC7fARB8SaSEgUDUG6aFfqEutUNCTPzzYE';
	document.body.appendChild(ELA_MAP.map_src);
};

// Instances
ELA_MAP.prototype = {
	init: function() {
		if (!ELA_MAP.map_src) return ELA_MAP.load_google();

		// Generate new map
		this.opt.center = new google.maps.LatLng(this.opt.lat, this.opt.lng);
		this.map = new google.maps.Map(this.ele, this.opt);

		// Generate new marker
		this.marker = new google.maps.Marker({
			animation: google.maps.Animation.DROP,
			position:  this.opt.center,
			title:     this.name,
			map:       this.map,
		});

		// InfWwindow
		if (this.txt) {
			this.info = new google.maps.InfoWindow({ content: this.txt, maxWidth: 600 });

			var cb = function() {
				this.info.open(this.map, this.marker);
			};
			google.maps.event.addListener(this.marker, 'click', cb.bind(this));
			cb.call(this);
		}
	}
};

/* ------------------------------------------------------------------- *|
 * jQuery page accents
 * docs:  http://api.jquery.com/
 * ------------------------------------------------------------------- */

// jQuery soft scroll
jQuery.fn.scroll_top = function (offset, cb) {
	if ( this.offset() ) jQuery('html, body').animate({
		scrollTop: Math.max(parseInt( this.offset().top, 10 )-offset, 0)
	}, 500, cb);
};
jQuery(document).ready(function() {

	// soft scroll links
	jQuery('a').on('click', function (e){
		e.target = $(e.target).closest('a')[0];
		if ( e.target.hash ) {
			var is_user = e.target.hash.match(/speaker-/) || e.target.hash.match(/sponsor-/);
			if (is_user) {
				jQuery(e.target.hash).addClass('active');
				setTimeout(function() { jQuery(e.target.hash).removeClass('active'); }, 5000);
			}

			jQuery( e.target.hash ).scroll_top(is_user ? 60 : 0, function() {
				if (e.target.hash == '#login') jQuery('#inputUser').focus();
				document.location.hash = e.target.hash;
			});
			e.preventDefault();
		}
	});

	// Profile image
	jQuery('#user_image').change(function() {
		if (this.files && this.files[0]) {
			var reader = new FileReader();
			reader.onload = function (e) {
				jQuery('#actual_user_image').attr('src', e.target.result);
			};
			reader.readAsDataURL(this.files[0]);
		}
	});
	jQuery('#profile_reset').click(function () {
		document.profile.reset();
		$('#actual_user_image').attr('src', 'data/usr/' + $('#orig_image').val());
	});
});
