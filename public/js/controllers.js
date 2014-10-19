(function() {
  var app = angular.module('animurecsControllers', []);

  app.controller('LandingPageController', ['$scope', '$http', function($scope, $http) {
    var page = $scope;
    page.user = {};
    $http.get('https://test.animurecs.com/api/users/shaldengeki').success(function(data) {
      page.user = data;
    });
  }]);

  app.controller('LoginController', ['$scope', '$http', function($scope, $http) {
    $scope.username = "";
    $scope.password = "";

    this.login = function() {
      $http.post('https://test.animurecs.com/api/users/' + $scope.username + '/log_in', {username: $scope.username, password: $scope.password})
        .success(function(data) {
          console.log("Sucessfully logged in!");
        })
        .error(function(data) {
          console.log("Could not log you in.")
        });
    }
  }])

})();