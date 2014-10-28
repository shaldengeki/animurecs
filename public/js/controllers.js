(function() {
  var app = angular.module('animurecsControllers', []);

  app.controller('ApplicationController', ['$scope', 'USER_LEVELS', 'Auth', function($scope, USER_LEVELS, Auth) {
    $scope.currentUser = Auth.currentUser();
    $scope.$watch(Auth.isAuthenticated, function(authed) {
      $scope.currentUser = Auth.currentUser();
      $scope.isAdmin = Auth.isAdmin();
      $scope.switched = Auth.switched();
      $scope.notSwitched = Auth.notSwitched();
    });
    $scope.userRoles = USER_LEVELS;

    $scope.setCurrentUser = function(user) {
      $scope.currentUser = user;
    }
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

  app.controller('NavbarSearchController', ['$scope', function($scope) {
    
  }]);

  app.controller('LogOutController', ['$scope', '$location', 'Auth', 'User', function($scope, $location, Auth, User) {
    User.logout({username: Auth.currentUser().username}, {username: Auth.currentUser().username}, function(data) {
      Auth.logout();
      $location.path('/');
    });
  }]);

  app.controller('LandingPageController', ['$scope', 'User', 'Auth', function($scope, User, Auth) {
    var page = $scope;
    page.user = User.get({
      username: 'shaldengeki'
    });
  }]);

  app.controller('DashboardController', ['$scope', function($scope) {

  }]);

  app.controller('UsersProfileController', ['$scope', '$routeParams', 'User', 'Auth', function($scope, $routeParams, User, Auth) {
    var profile = $scope;
    profile.user = User.get({
      username: $routeParams.username
    });
    profile.compatibility = User.compatibility({username: $routeParams.username});
  }]);

  app.controller('FriendGridController', ['$scope', '$routeParams', 'User', function($scope, $routeParams, User) {
    $scope.friends = User.friends({
      // TODO: make this rely on $scope.user
      username: $routeParams.username
    });
  }]);

})();