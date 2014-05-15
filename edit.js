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

controller('event-edit-attendee', ['$scope', '$controller', 'UserModal', function ($scope, $controller, UserModal) {
	angular.extend(this, $controller('event-attendee', {$scope: $scope}));

	$scope.edit = function (user, $event) {
		$event.preventDefault();
		UserModal.open( user.userID ).then( angular.extend.bind(undefined, user) );
	};
}]).

factory('UserModal', ['API', '$modal', '$sce', function (API, $modal, $sce) {
	var User = new API('user');

	return {
		open: function (userID) {
			var modalInstance = $modal.open({
				templateUrl: 'tpl/dlg/user.tpl.html',
				controller: 'event-user-modal',
				size: 'lg',
				resolve: {
					user: User.get.bind( User, userID )
				}
			});
			return modalInstance.result.then(function (user) {
				User.set(user); // Note: assumes successful
				user.safe = { // for event-edit-attendee
					firm:  $sce.trustAsHtml( user.firm ),
					name:  $sce.trustAsHtml( user.name ),
					title: $sce.trustAsHtml( user.title )
				};
				return user;
			});
		}
	};
}]).

controller('event-user-modal', ['$scope', '$modalInstance', 'user', function ($scope, $modalInstance, user) {
	$scope.user = user;

	$scope.ok = function () {
		$modalInstance.close( $scope.user );
	};

	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};
}]).

//  data-ng-non-bindable
controller('event-edit-agenda', ['$scope', 'API', '$sce', '$modal', function ($scope, API, $sce, $modal) {
	var conferenceID = document.getElementById('conferenceID').value ;

	var Session = new API('session', undefined, undefined, '/conferenceID/' + conferenceID);
	var UserMap = {}, User = new API('user', undefined, function() {
		for (var i = 0; i < User.list.length; i++) UserMap[User.list[i].userID] = User.list[i];
	});

	// Assign for pretty printing
	$scope.sessions = Session.list;
	$scope.getHTML = function(html) { return $sce.trustAsHtml(html); };

	// Editing functions
	$scope.add = function (location) {
		var new_item = {
			title: 'New Session',
			date: '2000-01-01',
			start: '01:00',
			end: '01:01',
			conferenceID: conferenceID,
		};

		// Grab from past element
		if (Session.list.length > 0) {
			var index = location == 'bot' ? Session.list.length - 1 : 0;
			new_item.end = Session.list[ index ].end;
			new_item.date = Session.list[ index ].date;
			new_item.start = Session.list[ index ].end;
		}

		Session.add(new_item).then(function (obj) {
			$scope.edit(obj.sessionID);
		});
	};
	$scope.edit = function(sessionID) {
		var modalInstance = $modal.open({
			templateUrl: 'tpl/dlg/agenda.tpl.html',
			controller: 'event-edit-agenda-modal',
			size: 'lg',
			resolve: {
				session: Session.get.bind(Session, sessionID),
				user: function() { return UserMap; }
			}
		});
		modalInstance.result.then( Session.set.bind(Session) );
	};
	$scope.rem = Session.rem.bind( Session );
}]).

controller('event-edit-agenda-modal', ['$scope', '$modalInstance', 'session', 'user', 'API', function ($scope, $modalInstance, session, user, API) {
	$scope.users = user;
	$scope.session = session;

	// Init speaker and file stuff;
	var File = new API('file', undefined, undefined, '/sessionID/' + session.sessionID);
	var Speaker = new API('speaker', undefined, function () {
		for (var i = 0; i < Speaker.list.length; i++) angular.extend(Speaker.list[i], user[Speaker.list[i].userID]);
	}, '/sessionID/' + session.sessionID);
	$scope.files = File.list;
	$scope.speakers = Speaker.list;

	// Speaker Functions
	$scope.new_speaker = {};
	$scope.add_speaker = function () {
		$scope.new_speaker.featured = 'true';
		$scope.new_speaker.sessionID = session.sessionID;
		Speaker.add($scope.new_speaker).then(function (item) {
			$scope.new_speaker = {};
			angular.extend(item, user[item.userID]);
		});
	};
	$scope.rem_speaker = Speaker.rem.bind( Speaker );
	$scope.set_speaker = Speaker.set.bind( Speaker );

	// Files funcitons
	$scope.new_file = {};
	$scope.add_file = function () {
		$scope.new_file.sessionID = session.sessionID;
		File.add($scope.new_file).then(function () {
			$scope.new_file = {};
		});
	};
	$scope.rem_file = File.rem.bind( File );
	$scope.set_file = File.set.bind( File );

	// Closing functions
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

directive('ngTinymce', function() { // requires jquery.tinymce.js and Global MCE_OBJ for settings
	return {
		require: 'ngModel',
		link: function ($scope, element, attrs, ngModel) {
			element.tinymce( angular.extend( MCE_OBJ || {}, {
				setup: function (editor) {
					editor.on('change', function (e) {
						if (this.isDirty()) this.save();
					});
					editor.on('SaveContent', function (e) {
						ngModel.$setViewValue(this.getContent());
						$scope.$apply();
					});
				}
			} ) );
		}
	}
}).

directive('colEditor', function () {
	return {
		replace: true,
		scope: {
			colField: '=',
			saveCb: '&'
		},
		template: '<td ng-class="{editing:active}"><div class="view" ng-click="start_editing()" ng-hide="active"><span ng-bind="colField ? colField : \'-\'"></span></div><form ng-submit="done_editing()"><input type="text" ng-show="active" class="edit form-control input-sm" ng-model="colField" ng-blur="done_editing()" edit-escape="undo_editing()" edit-focus="active"></form></td>',
		link: function (scope, elem, attrs) {
			var origional = null;
			scope.active = false;
			scope.start_editing = function () {
				origional = angular.copy(scope.colField);
				scope.active = true;
			};
			scope.done_editing = function () {
				if (scope.active && scope.colField != origional) scope.saveCb();
				scope.active = false;
			};
			scope.undo_editing = function () {
				scope.colField = origional;
				scope.done_editing();
			};
		}
	};
}).

directive('editEscape', function () {
	var ESCAPE_KEY = 27;
	return function (scope, elem, attrs) {
		elem.bind('keydown', function (event) {
			if (event.keyCode === ESCAPE_KEY) 
				scope.$apply(attrs.editEscape);
			event.stopPropagation();
		});
	};
}).

directive('editFocus', ['$timeout', function ($timeout) {
	return function (scope, elem, attrs) {
		scope.$watch(attrs.editFocus, function (newVal) {
			if (newVal) $timeout(function () {
				elem[0].focus();
			}, 0, false);
		});
	};
}]);
