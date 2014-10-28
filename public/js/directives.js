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
      restrict: 'A',
      templateUrl: '/partials/navbar-login.html'
    };
  });
  app.directive("navbarSearch", function() {
    return {
      restrict: 'A',
      templateUrl: '/partials/navbar-search.html'
    };
  });
  app.directive("navbarUser", function() {
    return {
      restrict: 'A',
      templateUrl: '/partials/navbar-user.html'
    };
  });

  app.directive("landingPage", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/landing-page.html'
    };
  });


  app.directive("friendSidebarGrid", function() {
    return {
      restrict: 'A',
      templateUrl: '/partials/users/friend-sidebar-grid.html'
    }
  })

  app.directive("footer", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/footer.html'
    };
  });
})();