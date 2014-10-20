(function() {
  var app = angular.module('animurecs', ['ngRoute', 'ngCookies', 'animurecsControllers', 'animurecsDirectives', 'animurecsServices']);

  app.constant('AUTH_EVENTS', {
    initializeSuccess: 'auth-initialize-success',
    loginSuccess: 'auth-login-success',
    loginFailed: 'auth-login-failed',
    logoutSuccess: 'auth-logout-success',
    sessionTimeout: 'auth-session-timeout',
    notAuthenticated: 'auth-not-authenticated',
    notAuthorized: 'auth-not-authorized'
  });
  app.constant('USER_LEVELS', {
    guest: 0,
    user: 1,
    moderator: 2,
    admin: 3
  });

  // check to ensure that the currently logged-in user is authorized to view each route, upon route change.
  app.run(['$rootScope', 'AUTH_EVENTS', 'Auth', function($rootScope, AUTH_EVENTS, Auth) {
    $rootScope.$on('$routeChangeStart', function(event, next) {
      var authorizedRoles = next.data.authorizedRoles;
      if (!Auth.isAuthorized(authorizedRoles)) {
        event.preventDefault();
        if (Auth.isAuthenticated()) {
          // user is logged in, but not allowed.
          $rootScope.$broadcast(AUTH_EVENTS.notAuthorized);
        } else {
          // user is neither logged in nor allowed.
          $rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
        }
      }
    });
  }]);

  // get the currently logged-in user, if any, on application run.
  app.run(['$cookieStore', '$rootScope', 'Auth', function($cookieStore, $rootScope, Auth) {
    var user = $cookieStore.get('user');
    if (user !== undefined) {
      Auth.login(user);
    }
  }]);

  // application routes.
  app.config(['$routeProvider', '$locationProvider', '$httpProvider', 'USER_LEVELS', function($routeProvider, $locationProvider, $httpProvider, USER_LEVELS) {
    $routeProvider
      .when('/', {
        templateUrl: '/partials/landing-page.html',
        controller: 'LandingPageController',
        data: {
          authorizedRoles: [USER_LEVELS.guest]
        }
      })
      .when('/dashboard', {
        templateUrl: '/partials/users/dashboard.html',
        controller: 'DashboardController',
        data: {
          authorizedRoles: [USER_LEVELS.user, USER_LEVELS.moderator, USER_LEVELS.admin]
        }
      })
      .when('/users/:username', {
        templateUrl: '/partials/users/profile.html',
        controller: 'UsersProfileController',
        data: {
          authorizedRoles: [USER_LEVELS.guest]
        }
      })
      .when('/log_out', {
        template: ' ',
        controller: 'LogOutController',
        data: {
          authorizedRoles: [USER_LEVELS.user, USER_LEVELS.moderator, USER_LEVELS.admin]
        }
      })
      .otherwise({
        redirectTo: '/'
      });

    $httpProvider.interceptors.push([
      '$injector',
      function($injector) {
        return $injector.get('AuthInterceptor');
      }
    ]);

    // use the HTML5 History API
    $locationProvider.html5Mode(true);
  }]);
})();