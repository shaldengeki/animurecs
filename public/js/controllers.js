(function() {
  var app = angular.module('animurecsControllers', []);

  app.controller('ApplicationController', ['$scope', 'USER_LEVELS', 'Auth', function($scope, USER_LEVELS, Auth) {
    $scope.currentUser = Auth.currentUser();
    $scope.$watch(Auth.isAuthenticated, function(authed) {
      $scope.currentUser = Auth.currentUser();
    });
    $scope.userRoles = USER_LEVELS;

    $scope.setCurrentUser = function(user) {
      $scope.currentUser = user;
    }
  }]);

  app.controller('LandingPageController', ['$scope', 'User', 'Auth', function($scope, User, Auth) {
    var page = $scope;
    page.user = User.get({
      username: 'shaldengeki'
    });
  }]);

  app.controller('LoginController', ['$scope', '$rootScope', '$location', 'User', 'AUTH_EVENTS', 'Auth', function($scope, $rootScope, $location, User, AUTH_EVENTS, Auth) {
    $scope.username = "";
    $scope.password = "";

    this.login = function() {
      User.login({
        username: $scope.username
      }, {
        username: $scope.username,
        password: $scope.password
      }, function(data) {
        $rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
        Auth.login(data);
        $scope.setCurrentUser(data);
        $location.path('/dashboard');
      }, function(data) {
        $rootScope.$broadcast(AUTH_EVENTS.loginFailed);
      });
    }
  }]);

  app.controller('NavbarSearchController', [function() {

  }]);

  app.controller('DashboardController', [function() {

  }]);

})();