angular.module('event-admin', ['helpers']).

controller('conf', ['$scope', 'API', function ($scope, API) {
	var Conference = new API('conference');
	$scope.confs = Conference.list;
	$scope.set_conf = Conference.set.bind( Conference );

	console.log('her');
}]);
