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
if (window.hasOwnProperty('jQuery')) {
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
}

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

angular.module('event-speaker', []).controller('event-speaker', ['$scope','$modal','ArrestDB','Notes',function ($scope, $modal, ArrestDB, Notes) {

	// Dialog Functions
	var User = new ArrestDB('user');
	$scope.show_user = function (userID, $event) {
		if ($event) $event.preventDefault();
		var instance = $modal.open({
			templateUrl: 'tpl/dlg/user.note.tpl.html',
			resolve: {
				user: User.get.bind(User, userID),
				note: Notes.getNote.bind($scope, 'user', userID),
			},
			controller: ['$scope', 'user', '$modalInstance', 'note', function ($scope, user, $modalInstance, note) {
				$scope.user = user;
				$scope.note = note;
				$scope.ok = function () { $modalInstance.close($scope.note); };
				$scope.cancel = function () { $modalInstance.dismiss('cancel'); };
			}]
		});
		instance.result.then( Notes.setNote );
	};
}]);

angular.module('event-agenda', []).controller('event-agenda', ['$scope','$controller','$modal','Notes',function ($scope, $controller, $modal, Notes) {
	angular.extend(this, $controller('event-speaker', {$scope: $scope}));

	// Notes on agenda item
	$scope.show_note = function (sessionID, name) {
		var instance = $modal.open({
			templateUrl: 'tpl/dlg/session.note.tpl.html',
			resolve: {
				session: function() { return { sessionID:sessionID, name:name }; },
				note: Notes.getNote.bind($scope, 'session', sessionID),
			},
			controller: ['$scope', 'session', 'note', '$modalInstance', function ($scope, session, note, $modalInstance) {
				$scope.session = session;
				$scope.note = note;
				$scope.ok = function () { $modalInstance.close($scope.note); };
				$scope.cancel = function () { $modalInstance.dismiss('cancel'); };
			}]
		});
		instance.result.then( Notes.setNote );
	};
}]);

angular.module('event-attendee', []).controller('event-attendee', ['$scope','$sce','ArrestDB','$controller',function ($scope, $sce, ArrestDB, $controller) {
	angular.extend(this, $controller('event-speaker', {$scope: $scope}));
	
	// Properly dispay bios
	$scope.data = []; // $scope.data = Atten.list;
	var conferenceID = document.getElementById('conferenceID').value ;
	var Atten = new ArrestDB('atten/' + conferenceID, undefined, function (res) {
		$scope.data = res;

		angular.forEach($scope.data, function (value) {
			value.bio = (value.bio) ? value.name + ' ' + value.bio : '';
		});
	});

	// Searching
	$scope.total_rows = function () {
		$scope.filtered_data = $scope.filtered_data ? $scope.filtered_data : [];
		var tail = ($scope.filtered_data.length == $scope.data.length) ? '' : (' of ' + $scope.data.length);
		return 'Total: ' + $scope.filtered_data.length + tail;
	};

	// Pagination
	$scope.limits = [5,15,25,50,100];
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

angular.module('helpers', [
	'ArrestDB'
]).

constant('ArrestDB_base', './db2/').

filter('pagination', function () {
	return function (inputArray, selectedPage, pageSize) {
		var start = (selectedPage-1) * pageSize;
		return inputArray.slice(start, start + pageSize);
	};
}).

factory('Notes', ['ArrestDB', '$q', function (ArrestDB, $q) {
	var userID = document.getElementById('userID').value;
	var Note = new ArrestDB('note', undefined, undefined, '/userID/' + userID);

	var getNote = function (type, id) {
		for (var i = 0, len = Note.list.length; i < len; i++) // Look for previous note
			if (Note.list[i]['dest_' + type + 'ID'] == id) 
				return $q.when(Note.list[i]);

		return Note.add({ // Create new note
			userID: userID,
			dest_userID: (type == 'user') ? id : null,
			dest_sessionID: (type == 'session') ? id : null,
			note: '',
			stamp: Date.now(),
		});
	};
	var setNote = function (data) {
		data.stamp = Date.now();
		Note[ data.note ? 'set' : 'rem' ]( data );
	};

	return {
		getNote: getNote,
		setNote: setNote,
	}
}]).

config(['$locationProvider', function ($locationProvider) {
	$locationProvider.html5Mode(true); // fix link hashes
}]).

directive('textAutoScale', function () {
	return {
		restrict: 'C',
		link: function(scope, element, attrs) {
			element.on('keyup', function (e) {
				e.target.style.height = "1px";
    			e.target.style.height = (25+e.target.scrollHeight)+"px";
			});
			scope.$watch(function () { return element.is(':visible'); }, function () { element.keyup(); });
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
}]).

controller('event-user-modal', ['$scope', '$modalInstance', 'user', 'is_admin', 'User', function ($scope, $modalInstance, user, is_admin, User) {
	$scope.is_admin = is_admin;
	$scope.user = user;
	$scope.ok = function () { $modalInstance.close( $scope.user ); };
	$scope.cancel = $modalInstance.dismiss.bind(undefined, 'cancel');
	$scope.rem = function (user) {
		User.rem( user ).then( $modalInstance.dismiss );
	};
}]).

factory('UserModal', ['ArrestDB', '$modal', '$sce', '$q', function (ArrestDB, $modal, $sce, $q) {
	var service = {
		open: function (userID, their_user, is_admin) {
			var User = their_user || new ArrestDB('user');
			var modalInstance = $modal.open({
				templateUrl: 'tpl/dlg/user.tpl.html',
				controller: 'event-user-modal',
				size: 'lg',
				resolve: {
					user: User.get.bind( User, userID ),
					is_admin: function () { return is_admin || false; },
					User: function () { return User; }
				}
			});
			return modalInstance.result.then( User.set.bind( User ) ); // Chained promises
		},

		add: function (their_user, is_admin) {
			var User = their_user || new ArrestDB('user'), result = $q.defer();
			User.add({
				name: 'New User ' + (Math.random() * 1e4 >> 0), 
			}).then(function (blank_user) {
				service.open( blank_user.userID, User, is_admin ).then( result.resolve, function () {
					User.rem( blank_user ).then( result.reject );
				});
			});
			return result.promise;
		}
	};
	return service;
}]);