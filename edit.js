"use strict";

/* ------------------------------------------------------------------- *|
 * Google Maps Wrapper (Nathan Woods: April 28, 2014)
 * docs: https://developers.google.com/maps/documentation/javascript/reference
 * ------------------------------------------------------------------- */

// Constructor
var ELA_MAP_EDIT = function (ele, inp) {
	this.ele = ele; // DOM output element (display map + controls)
	this.inp = inp; // DOM input element (to store string options) // TODO: write options back on change
	this.opt = JSON.parse(inp.value.replace(/'/g, '"'));
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

		// Update options on map change
		var update_input = function () {
			this.inp.value = JSON.stringify(this.get_opts()).replace(/"/g, "'");
		};
		google.maps.event.addListener( this.map, 'zoom_changed', update_input.bind(this) );
		google.maps.event.addListener( this.marker, 'position_changed', update_input.bind(this) );
		update_input.call(this);
	},

	get_opts: function() {
		return {
			lat: this.marker.position.lat(),
			lng: this.marker.position.lng(),
			zoom: this.map.zoom,
		};
	}
};

/* ------------------------------------------------------------------- *|
 * angular page accents
 * docs:  https://docs.angularjs.org/api/
 * ------------------------------------------------------------------- */

angular.module('event-edit', ['event']).

//  data-ng-non-bindable
controller('event-edit-agenda', ['$scope', 'API', '$sce', '$modal', function ($scope, API, $sce, $modal) {
	var conferenceID = document.getElementById('conferenceID').value ;

	var File = new API('file');
	var Session = new API('session', undefined, undefined, '/conferenceID/' + conferenceID);
	var Speaker = new API('speaker');
	var UserMap = {}, User = new API('user', undefined, function() {
		for (var i = 0; i < User.list.length; i++) UserMap[User.list[i].userID] = User.list[i];
	});

	$scope.sessions = Session.list;
	$scope.getHTML = function(html) { return $sce.trustAsHtml(html); };

	$scope.edit = function(sessionID) {
		var modalInstance = $modal.open({
			templateUrl: 'tpl/dlg/agenda.tpl.html',
			controller: 'event-edit-agenda-modal',
			size: 'lg',
			resolve: {
				session: Session.get.bind(Session, sessionID),
				speaker: Speaker.all.bind(Speaker, '/sessionID/' + sessionID),
				user: function() { return UserMap; },
				file: File.all.bind(File, '/sessionID/' + sessionID)
			}
		});
		modalInstance.result.then( Session.set.bind(Session) );
	};
}]).

controller('event-edit-agenda-modal', ['$scope', '$modalInstance', 'session', 'speaker', 'user', 'file', function ($scope, $modalInstance, session, speaker, user, file) {
	$scope.files = file;
	$scope.users = user;
	$scope.session = session;
	$scope.speaker = speaker;
	for (var i = 0; i < speaker.length; i++) angular.extend(speaker[i], user[speaker[i].userID]);

	

	$scope.ok = function () { $modalInstance.close($scope.session); };
	$scope.cancel = function () { $modalInstance.dismiss('cancel'); };
}]).

// http://justinklemm.com/angularjs-filter-ordering-objects-ngrepeat/
filter('orderObjectBy', function() {
	return function (items, field, reverse) {
		var filtered = [];
		angular.forEach(items, function (item) {
			filtered.push(item);
		});
		filtered.sort(function (a, b) {
			return (a[field] > b[field] ? 1 : -1);
		});
		if(reverse) filtered.reverse();
		return filtered;
	};
}).

directive('ngTinymce', function() { // requires jquery.tinymce.js
	return {
		require: 'ngModel',
		link: function ($scope, element, attrs, ngModel) {
			element.tinymce({
				menubar: false,
				statusbar : false,
				toolbar_items_size: 'small',
				setup: function (editor) {
					editor.on('change', function (e) {
						if (this.isDirty()) this.save();
					});
					editor.on('SaveContent', function (e) {
						ngModel.$setViewValue(this.getContent());
						$scope.$apply();
					});
				}
			});
		}
	}
});
