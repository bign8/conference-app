angular.module('event-admin', ['helpers', 'ui.bootstrap']).

controller('conf', ['$scope', 'API', function ($scope, API) {
	var Conference = new API('conference');
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

controller('quiz', ['$scope', 'API', '$q', function ($scope, API, $q) {
	var load_attendee = $q.defer(), load_user = $q.defer();

	var Conference = new API('conference');
	var Attendee = new API('attendee', undefined, load_attendee.resolve);
	var User = new API('user', undefined, load_user.resolve);
	$scope.confs = Conference.list;
	$scope.users = User.list;

	API.left_join( User, Attendee );

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
	$scope.limits = [8,16,32,64,128];
	$scope.limit = $scope.limits[0];
	$scope.page = 1;

	$scope.open_this = function ($event) {
		$event.preventDefault();
		for (var i = 0; i < Conference.list.length; i++) 
			if (Conference.list[i].conferenceID == $scope.myEvent) 
				document.location = './' + Conference.list[i].slug;
	};
}]).

controller('upld', ['$scope', 'API', function ($scope, API) {
	var Conference = new API('conference');
	$scope.confs = Conference.list;
}]);
