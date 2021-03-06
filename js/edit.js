"use strict";

/* ------------------------------------------------------------------- *|
 * Google Maps Wrapper (Nathan Woods: April 28, 2014)
 * docs: https://developers.google.com/maps/documentation/javascript/reference
 * ------------------------------------------------------------------- */

// Constructor
var ELA_MAP_EDIT = function (ele, inp, search) {
	this.ele = ele; // DOM output element (display map + controls)
	this.inp = inp; // DOM input element (to store string options)
	this.search = search; // DOM input element (for searching for places)
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
	ELA_MAP_EDIT.map_src.src  = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places&callback=ELA_MAP_EDIT.google_maps_loaded&key=AIzaSyC7fARB8SaSEgUDUG6aFfqEutUNCTPzzYE';
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

		// Init search (https://developers.google.com/maps/documentation/javascript/examples/places-searchbox)
		this.map.controls[google.maps.ControlPosition.TOP_LEFT].push( this.search );
		this.search_box = new google.maps.places.SearchBox( this.search );
		$( this.search ).keydown(function (event){ if(event.keyCode == 13) { event.preventDefault(); return false; } });
		var update_search = function () {
			var places = this.search_box.getPlaces();
			this.map.setCenter( places[0].geometry.location );
			this.marker.setPosition( places[0].geometry.location );
		};
		google.maps.event.addListener(this.search_box, 'places_changed', update_search.bind(this) );

		// Bias search
		var update_bounds = function () {
			this.search_box.setBounds( this.map.getBounds() );
		};
		google.maps.event.addListener(this.map, 'bounds_changed', update_bounds.bind(this) )
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

controller('event-edit-head', angular.noop).

controller('event-edit-attendee', ['$scope', 'UserModal', 'ArrestDB', '$q', function ($scope, UserModal, ArrestDB, $q) {

	/* --- start initialization --- */
	var conferenceID = document.getElementById('conferenceID').value ;
	var User = new ArrestDB('user');
	var Attendee = new ArrestDB('attendee', null, null, '/conferenceID/' + conferenceID);
	$scope.users = ArrestDB.left_join(User, Attendee);
	/* --- end initialization --- */

	/* --- start navigation --- */
	$scope.total_rows = function () { // Searching
		$scope.filtered_data = $scope.filtered_data ? $scope.filtered_data : [];
		var tail = ($scope.filtered_data.length == Attendee.list.length) ? '' : (' of ' + Attendee.list.length);
		return 'Total: ' + $scope.filtered_data.length + tail;
	};
	$scope.limits = [15,25,50,100]; // Pagination
	$scope.limit = $scope.limits[0];
	$scope.page = 1;
	$scope.fields = [ // Order By
		{field: ['name'], disp: 'Name'},
		{field: ['title','name'], disp: 'Title'},
		{field: ['firm','name'], disp: 'Firm'},
	];
	$scope.field = $scope.fields[0].field;
	$scope.sort_order = false;
	/* --- end navigation --- */

	/* --- start editing --- */
	$scope.new_attendee = null;
	$scope.add = function () {

		// Create new user or use old one
		var new_attendee = $scope.new_attendee ? $q.when( $scope.new_attendee ) : UserModal.add( User );

		// Add attendee
		new_attendee.then(function (user) {
			Attendee.add({
				userID: user.userID,
				conferenceID: conferenceID
			}).then(function () {
				$scope.new_attendee = null;
			});
		});
	};
	$scope.edit = function (user, $event) {
		$event.preventDefault();
		UserModal.open( user.userID, User );
	};
	$scope.rem = function (user, $event) {
		$event.preventDefault();
		Attendee.rem( user );
	};
	/* --- end editing --- */
}]).

//  data-ng-non-bindable
controller('event-edit-agenda', ['$scope', 'ArrestDB', '$sce', '$modal', function ($scope, ArrestDB, $sce, $modal) {
	var conferenceID = document.getElementById('conferenceID').value ;

	var Session = new ArrestDB('session', undefined, undefined, '/conferenceID/' + conferenceID);
	var UserMap = {}, User = new ArrestDB('user', undefined, function() {
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

controller('event-edit-agenda-modal', ['$scope', '$modalInstance', 'session', 'user', 'ArrestDB', 'UserModal', function ($scope, $modalInstance, session, user, ArrestDB, UserModal) {
	$scope.users = user;
	$scope.session = session;

	// Init speaker and file stuff;
	var File = new ArrestDB('file', undefined, undefined, '/sessionID/' + session.sessionID);
	var Speaker = new ArrestDB('speaker', undefined, function () {
		for (var i = 0; i < Speaker.list.length; i++) angular.extend(Speaker.list[i], user[Speaker.list[i].userID]);
	}, '/sessionID/' + session.sessionID);
	$scope.files = File.list;
	$scope.speakers = Speaker.list;

	// Speaker Functions
	$scope.new_speaker = {};
	$scope.add_speaker = function () {
		if (!$scope.new_speaker.userID) return alert('Adding users not allowed here (yet)');
		$scope.new_speaker.featured = 'true';
		$scope.new_speaker.sessionID = session.sessionID;
		Speaker.add($scope.new_speaker).then(function (item) {
			$scope.new_speaker = {};
			angular.extend(item, user[item.userID]);
		});
	};
	$scope.rem_speaker = Speaker.rem.bind( Speaker );
	$scope.set_speaker = Speaker.set.bind( Speaker );
	$scope.edit_speaker = function (user) {
		UserModal.open( user.userID ).then( angular.extend.bind(undefined, user) );
	};

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

controller('event-edit-sponsor', ['$scope', '$modal', 'ArrestDB', '$q', function ($scope, $modal, ArrestDB, $q) {
	var conferenceID = document.getElementById('conferenceID').value ;
	var Company = new ArrestDB('company');
	var Sponsor = new ArrestDB('sponsor', null, null, '/conferenceID/' + conferenceID);
	$scope.companies = ArrestDB.left_join( Company, Sponsor );

	// Sponsor Editing
	$scope.new_sponsor = null;
	$scope.add = function () {
		var new_sponsor = $scope.new_sponsor ? $q.when( $scope.new_sponsor ) : Company.add({
			name: 'New Company ' + (Math.random() * 10000 >> 0)
		}).then(function (company) {
			return $scope.edit(company);
		});
		
		new_sponsor.then(function (company) {
			Sponsor.add({
				conferenceID: conferenceID,
				priority: 0,
				advert: null,
				companyID: company.companyID,
			}).then(function () {
				$scope.new_sponsor = null;
			});	
		});
	};
	$scope.rem = Sponsor.rem.bind( Sponsor );
	$scope.set = Sponsor.set.bind( Sponsor );
	$scope.move = function (obj, diff) {
		obj.priority = parseInt(obj.priority) + diff;
		$scope.set(obj);
	};

	// Company Editing
	$scope.edit = function (company) {
		var modalInstance = $modal.open({
			templateUrl: 'tpl/dlg/sponsor.tpl.html',
			controller: 'event-edit-sponsor-modal',
			size: 'lg',
			resolve: {
				company: function() { return company; },
			}
		});
		return modalInstance.result.then( Company.set.bind( Company ) );
	};
}]).

controller('event-edit-sponsor-modal', ['$scope','$modalInstance','company','ArrestDB','UserModal',function ($scope,$modalInstance,company,ArrestDB,UserModal) {
	
	// init data
	$scope.company = company;
	var Rep  = new ArrestDB('rep' , null, null, '/sponsorID/' + company.sponsorID);
	var User = new ArrestDB('user');
	$scope.users = ArrestDB.left_join(User, Rep);

	// Reps editing
	$scope.edit = function (user) {
		UserModal.open( user.userID, User );
	};
	$scope.rem = Rep.rem.bind( Rep );
	$scope.new_rep = null;
	$scope.add = function () {
		if (!$scope.new_rep) return alert('new users not yet allowed');
		Rep.add({
			sponsorID: company.sponsorID,
			userID: $scope.new_rep.userID,
		}).then(function () {
			$scope.new_rep = null;
		});
	};

	// Closing functions
	$scope.ok = function () { $modalInstance.close($scope.company); };
	$scope.cancel = $modalInstance.dismiss.bind(null, 'cancel');
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

directive('ngInitial', function() {
	return {
		restrict: 'A',
		controller: ['$scope', '$attrs', '$parse', function ($scope, $attrs, $parse) {
			$parse( $attrs.ngModel ).assign( $scope, $attrs.ngInitial || $attrs.value );
		}]
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
});
