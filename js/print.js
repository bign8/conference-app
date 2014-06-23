angular.module('print', [
	'helpers',
]).

controller('print', ['$scope', 'ArrestDB', '$location', function ($scope, ArrestDB, $location) {
	var Conference = new ArrestDB('conference');
	var Attendee = new ArrestDB('attendee');
	var User = new ArrestDB('user');

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
	$scope.show_names = true;
	$scope.view = 'list';
	$scope.$watch('myEvent', function (val) {
		if (!val) $scope.myEvent = undefined;
	});
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
});