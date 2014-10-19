(function() {
  var app = angular.module('animurecs', ['ngRoute', 'animurecsControllers', 'animurecsDirectives', 'animurecsServices']);
  app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
    $routeProvider
      .when('/', {
        templateUrl: '/partials/landing-page.html',
        controller: 'LandingPageController'
      })
      .otherwise({
        redirectTo: '/'
      });

    // use the HTML5 History API
    $locationProvider.html5Mode(true);      
  }]);
})();