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


/* ------------------------------------------------------------------- *|
 * angular page accents
 * docs:  https://docs.angularjs.org/api/
 * ------------------------------------------------------------------- */

angular.module('event', [
	'event-attendee',
	'helpers',
	'ui.bootstrap',
]);

angular.module('event-attendee', []).controller('event-attendee', ['$scope', '$sce', function ($scope, $sce) {
	
	// Parse data (http://regexpal.com/)
	var data, obj, regex = />([^<]*).*[\s]+[^>]+>([^<]*)<.*[\s]+[^>]+>([\s]+[^<]*)<.*[\s]+.*tel:([^"]*).*[\s]+.*mailto:([^"]*)/;
	$scope.data = [];
	$('#attendee tr.data').each(function (i, e){
		data = e.innerHTML.match(regex);
		obj = {
			name : data[1].trim(),
			title: data[2].trim(),
			firm : data[3].trim(),
			phone: data[4].trim(),
			email: data[5].trim(),
		};
		obj.safe = {
			name : $sce.trustAsHtml(obj.name ),
			title: $sce.trustAsHtml(obj.title),
			firm : $sce.trustAsHtml(obj.firm ),
		};
		$scope.data.push(obj);
	});

	// Searching
	$scope.total_rows = function () {
		$scope.filtered_data = $scope.filtered_data ? $scope.filtered_data : [];
		var tail = ($scope.filtered_data.length == $scope.data.length) ? '' : (' of ' + $scope.data.length);
		return 'Total: ' + $scope.filtered_data.length + tail;
	};

	// Pagination
	$scope.limits = [15,25,50,100];
	$scope.limit = $scope.limits[0];
	$scope.page = 1;

	// Order By
	$scope.fields = [
		{field: ['name'], disp: 'Name'},
		{field: ['title','name'], disp: 'Title'},
		{field: ['firm','name'], disp: 'Firm'},
	];
	$scope.field = $scope.fields[0].field;
	$scope.sort_order = false;
}]);

angular.module('helpers', []).filter('pagination', function () {
	return function (inputArray, selectedPage, pageSize) {
		var start = (selectedPage-1) * pageSize;
		return inputArray.slice(start, start + pageSize);
	};
});