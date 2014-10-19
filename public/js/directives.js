(function() {
  var app = angular.module('animurecsDirectives', []);
  app.directive("navbar", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/navbar.html'
    };
  });
  app.directive("navbarLogin", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/navbar-login.html'
    };
  });
  app.directive("navbarSearch", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/navbar-search.html'
    };
  });
  app.directive("landingPage", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/landing-page.html'
    };
  });
  app.directive("footer", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/footer.html'
    };
  });
})();