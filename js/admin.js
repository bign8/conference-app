angular.module('event-admin', ['helpers', 'ui.bootstrap']).

controller('conf', ['$scope', 'ArrestDB', function ($scope, ArrestDB) {
	var Conference = new ArrestDB('conference');
	$scope.confs = Conference.list;
	$scope.set_conf = Conference.set.bind( Conference );
	$scope.rem_conf = function (conf) {
		if (confirm('Delete "' + conf.title + '"?')) Conference.rem( conf );
	};

	$scope.mod = {};
	var val_slug = /^[a-z0-9]+$/;
	$scope.save = function () {
		if (!val_slug.test($scope.mod.slug)) return alert('Invalid Slug');
		for (var i = 0; i < Conference.list.length; i++)
			if (Conference.list[i].slug == $scope.mod.slug) return alert('Duplicate Slug');

		Conference.add($scope.mod).then(function() {
			$scope.mod = {};
		});
	};
}]).

controller('quiz', ['$scope', 'ArrestDB', 'UserModal', function ($scope, ArrestDB, UserModal) {
	var Conference = new ArrestDB('conference');
	var Attendee = new ArrestDB('attendee');
	var User = new ArrestDB('user');

	// Add usernames to bios!
	User.add_cb(function (res) {
		$scope.shuffle();
		return res;
	});

	// Format start_stamp for grouping
	Conference.add_cb(function (res) {
		angular.forEach(res, function (evt) {
			try {
				evt.start_year = evt.start_stamp.substr(0, evt.start_stamp.indexOf('-')) || 'Unknown';
			} catch (e) {
				evt.start_year = 'Unknown';
			}
		});
		return res;
	});

	$scope.confs = Conference.list;
	$scope.users = User.list;

	ArrestDB.left_join_many( User, Attendee, 'conferenceID' ); // attending 2 conferences

	// Controls
	$scope.view = 'tile';
	$scope.show_me = function (user) {
		alert(user.name + '\n' + user.title + '\n' + user.firm);
	};
	$scope.shuffle = function () {
		for (var i = 0, l = $scope.users.length; i < l; i++) $scope.users[i].random = Math.random();
	};
	$scope.$watch('myEvent', function (val) {
		if (!val) $scope.myEvent = undefined;
	});

	// Pagination
	$scope.limits = [4,8,16,32,64,128];
	$scope.limit = $scope.limits[0];
	$scope.page = 1;

	$scope.open_this = function ($event) {
		$event.preventDefault();
		for (var i = 0; i < Conference.list.length; i++) 
			if (Conference.list[i].conferenceID == $scope.myEvent) 
				document.location = './' + Conference.list[i].slug;
	};

	$scope.edit_user = function (user) {
		UserModal.open( user.userID, User, true );
	};
	$scope.add_user = function () {
		UserModal.add( User, true );
	};
}]).

filter('isAttending', function () {
	return function (arr, conferenceID) {
		if (!conferenceID) return arr;
		var out = [];
		for (var i = 0, l = arr.length; i < l; i++) if (arr[i].conferenceID.indexOf(conferenceID) >= 0) out.push(arr[i]);
		return out;
	};
}).

filter('hasImg', function () {
	return function (arr) {
		var out = [];
		for (var i = 0, l = arr.length; i < l; i++) if (arr[i].photo) out.push(arr[i]);
		return out;
	};
}).

controller('upld', ['$scope', 'ArrestDB', function ($scope, ArrestDB) {
	var Conference = new ArrestDB('conference');
	$scope.confs = Conference.list;
}]);
