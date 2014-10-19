(function() {
  var app = angular.module('animurecs', ['ngRoute', 'animurecsControllers', 'animurecsDirectives']);
  app.config(['$routeProvider', function($routeProvider) {
    $routeProvider
      .when('/', {
        templateUrl: '/partials/landing-page.html',
        controller: 'LandingPageController'
      })
      .otherwise({
        redirectTo: '/'
      });
  }]);
})();