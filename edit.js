"use strict";

/* ------------------------------------------------------------------- *|
 * Google Maps Wrapper (Nathan Woods: April 28, 2014)
 * docs: https://developers.google.com/maps/documentation/javascript/reference
 * ------------------------------------------------------------------- */

// Constructor
var ELA_MAP_EDIT = function (ele, opt, name) {
	this.ele = ele;
	this.opt = opt || {};
	this.name = name;
	ELA_MAP_EDIT._instances.push(this);
	this.init();
};

// Global Object Attributes
ELA_MAP_EDIT._instances  = [];

// Global Object functions
ELA_MAP_EDIT.google_maps_loaded = function() {
	for (var i = 0; i < ELA_MAP_EDIT._instances.length; i++)
		ELA_MAP_EDIT._instances[i].init();
};
ELA_MAP_EDIT.load_google = function() {
	if (ELA_MAP_EDIT.map_src) return;
	ELA_MAP_EDIT.map_src  = document.createElement('script');
	ELA_MAP_EDIT.map_src.type = 'text/javascript';
	ELA_MAP_EDIT.map_src.src  = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&callback=ELA_MAP_EDIT.google_maps_loaded&key=AIzaSyC7fARB8SaSEgUDUG6aFfqEutUNCTPzzYE';
	document.body.appendChild(ELA_MAP_EDIT.map_src);
};

// Instances
ELA_MAP_EDIT.prototype = {
	init: function() {
		if (!ELA_MAP_EDIT.map_src) return ELA_MAP_EDIT.load_google();

		// Default Options
		this.opt.lat  = this.opt.lat  ? this.opt.lat  :   46.59605657583841; //   39.75024007131259
		this.opt.lng  = this.opt.lng  ? this.opt.lng  : -112.03749943418501; // -104.99197203559874
		this.opt.zoom = this.opt.zoom ? this.opt.zoom :   12; // 13

		// Forced Options
		this.opt.scrollwheel       = false;
		this.opt.mapTypecontrol    = false;
		this.opt.streetViewControl = false;

		// Generate new map
		this.opt.center = new google.maps.LatLng(this.opt.lat, this.opt.lng);
		this.map = new google.maps.Map(this.ele, this.opt);

		// Generate new marker
		this.marker = new google.maps.Marker({
			animation: google.maps.Animation.DROP,
			draggable: true,
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
	},

	get_opts: function() {
		return {
			lat: this.marker.position.lat(),
			lng: this.marker.position.lng(),
			zoom: this.map.zoom,
		};
	}
};
