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
jQuery.fn.scroll_top = function (cb) {
	if ( this.offset() ) jQuery('html, body').animate({
		scrollTop: Math.max(parseInt( this.offset().top, 10 ), 0)
	}, 500, cb);
};
jQuery(document).ready(function() {

	// soft scroll links
	jQuery('a').on('click', function (e){
		e.target = $(e.target).closest('a')[0];
		if ( e.target.hash ) {
			jQuery( e.target.hash ).scroll_top(function() {
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
	'event-agenda',
	'event-attendee',
	'event-speaker',
	'helpers',
	'ui.bootstrap',
]);

angular.module('event-speaker', []).controller('event-speaker', ['$scope', '$modal', 'API', function ($scope, $modal, API) {
	var User = new API('user');
	$scope.show_user = function (userID) {
		var instance = $modal.open({
			templateUrl: 'tpl/user.dialog.tpl.html',
			resolve: { user: User.get.bind(User, userID) },
			controller: ['$scope', 'user', '$modalInstance', function ($scope, user, $modalInstance) {
				$scope.user = user;
				$scope.ok = function () { $modalInstance.close($scope.person); };
				$scope.cancel = function () { $modalInstance.dismiss('cancel'); };
			}]
		});
		// TODO: Notes on speaker
	};
}]);

angular.module('event-agenda', []).controller('event-agenda', ['$scope', '$controller', function ($scope, $controller) {
	angular.extend(this, $controller('event-speaker', {$scope: $scope}));
	// TODO: Notes on agenda item
}]);

angular.module('event-attendee', []).controller('event-attendee', ['$scope', '$sce', function ($scope, $sce) {
	
	// Parse data (http://regexpal.com/)
	var data, obj, regex = /([0-9]+)">([^<]*).*[\s]+[^>]+>([^<]*)<.*[\s]+[^>]+>([\s]+[^<]*)<.*[\s]+.*tel:([^"]*).*[\s]+.*mailto:([^"]*)/;
	$scope.data = [];
	$('#attendee tr.data').each(function (i, e){
		data = e.innerHTML.match(regex);
		obj = {
			userID: data[1].trim(),
			name  : data[2].trim(),
			title : data[3].trim(),
			firm  : data[4].trim(),
			phone : data[5].trim(),
			email : data[6].trim(),
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
}).factory('API', ['$http', function ($http) { // TODO: improve with browser data cashe
	var base = './db/';
	var cleanup = function (result) { return result.data.hasOwnProperty('error') ? [] : result.data; };
	var rem_obj = function (item) { this.list.splice(this.list.indexOf(item), 1); };
	var add_obj = function (item, data) {
		item[ this.id ] = data.success.data;
		this.list.push(item);
		return item;
	};
	var mod_obj = function (item, data) {
		if (data.hasOwnProperty('success')) for (var i = 0; i < this.list.length; i++) if (this.list[i][this.id] == item[this.id]) {
			this.list[i] = item;
			break;
		}
		return data;
	};
	var service = function(table, identifier, cb, suffix) {
		this.list = [];
		this.table = table;
		this.id = identifier || (table + 'ID'); // standard convention (tablename + ID, ie: faq = faqID)
		this.all(suffix).then(angular.extend.bind(undefined, this.list)).then(cb);
	};
	service.prototype = {
		all: function (suffix) {
			return $http.get(base + this.table + (suffix ? suffix : '')).then( cleanup.bind(this) );
		},
		get: function (itemID, suffix) {
			return $http.get(base + this.table + '/' + itemID + (suffix ? suffix : '')).then( cleanup.bind(this) );
		},
		set: function (item) {
			return $http.put(base + this.table + '/' + item[ this.id ], item).then( cleanup.bind(this) ).then( mod_obj.bind(this, item) );
		},
		rem: function (item) {
			return $http.delete(base + this.table + '/' + item[ this.id ]).then( cleanup.bind(this) ).then( rem_obj.bind(this, item) );
		},
		add: function (item) {
			return $http.post(base + this.table, item).then( cleanup.bind(this) ).then( add_obj.bind(this, item) );
		}
	};
	return service;
}]).config(['$locationProvider', function ($locationProvider) {
	$locationProvider.html5Mode(true); // fix link hashes
}]);