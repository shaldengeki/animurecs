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

  app.directive("entryInlineForm", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/entry-inline-form.html'
    }
  });


  app.directive("userSidebarAvatar", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/users/user-sidebar-avatar.html'
    }
  });
  app.directive("userSidebarFriends", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/users/user-sidebar-friends.html'
    }
  });
  app.directive("userProfileInfo", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/users/user-profile-info.html'
    }
  });
  app.directive("userProfileTabs", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/users/user-profile-tabs.html'
    }
  });

  app.directive("footer", function() {
    return {
      restrict: 'E',
      templateUrl: '/partials/footer.html'
    };
  });
})();