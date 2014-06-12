angular.module('print', [
	'helpers',
]).

controller('print', ['$scope', 'API', '$location', function ($scope, API, $location) {
	var Conference = new API('conference');
	var Attendee = new API('attendee');
	var User = new API('user');

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

	API.left_join_many( User, Attendee, 'conferenceID' ); // attending 2 conferences

	// Controls
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