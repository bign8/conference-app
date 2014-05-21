angular.module('event-admin', ['helpers']).

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
}]);
