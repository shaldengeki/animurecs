(function() {
  var app = angular.module('animurecsControllers', []);

  app.controller('LandingPageController', ['$scope', 'User', function($scope, User) {
    var page = $scope;
    page.user = User.get({
      username: 'shaldengeki'
    });
  }]);

  app.controller('LoginController', ['$scope', 'User', function($scope, User) {
    $scope.username = "";
    $scope.password = "";

    this.login = function() {
      User.login({
        username: $scope.username
      }, {
        username: $scope.username,
        password: $scope.password
      }, function(data) {
        console.log("Successfully logged in!");
      }, function(data) {
        console.log("Could not log you in.");
      });
    }
  }])

})();