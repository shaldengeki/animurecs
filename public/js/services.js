(function() {
  var app = angular.module('animurecsServices', ['ngResource', 'ngCookies']);

  app.factory('AuthInterceptor', ['$rootScope', '$q', 'AUTH_EVENTS', function ($rootScope, $q, AUTH_EVENTS) {
    return {
      responseError: function (response) { 
        $rootScope.$broadcast({
          401: AUTH_EVENTS.notAuthenticated,
          403: AUTH_EVENTS.notAuthorized,
          419: AUTH_EVENTS.sessionTimeout,
          440: AUTH_EVENTS.sessionTimeout
        }[response.status], response);
        return $q.reject(response);
      }
    };
  }]);

  app.factory('User', ['$resource', 'Auth',
    function($resource){
      return $resource('/api/users/:username/show', {}, {
        get: {method: 'GET'},
        save: {method: 'POST', url: '/api/users/:username/edit', withCredentials: true},
        query: {method:'GET', url: '/api/users', isArray: true},
        delete: {method: 'DELETE', url: '/api/users/:username/delete', withCredentials: true},
        login: {
          method: 'POST',
          url: '/api/users/:username/log_in',
          withCredentials: true
        },
        logout: {
          method: 'POST',
          url: '/api/users/:username/log_out',
          withCredentials: true
        },
        getCurrent: {
          method: 'GET',
          url: '/api/account/',
          withCredentials: true
        }
      });
    }]);

  app.factory('Auth', ['$cookieStore', 'User', 'USER_LEVELS', function($cookieStore, User, USER_LEVELS) {
    var user = null;
    return {
      login: function(u) {
        user = {
          id: u.id,
          username: u.username,
          usermask: u.usermask,
          switched: null
        }

        // Session.create(u.id, u.username, u.usermask);
        $cookieStore.put('user', u);
        return u;
      },
      logout: function() {
        user = null;
      },
      isAuthenticated: function() {
        return !!user && !!user.id;
      },
      isAuthorized: function(authorizedRoles) {
        if (!angular.isArray(authorizedRoles)) {
          authorizedRoles = [authorizedRoles];
        }
        if (authorizedRoles.indexOf(0) >= 0 && !user) {
          // public route.
          return true;
        }
        return authorizedRoles.some(function(r) { return !!user && user.usermask & Math.pow(2, r); });
      },
      currentUser: function() {
        return user;
      },
      isAdmin: function() {
        return !!user && user.usermask & Math.pow(2, USER_LEVELS.admin);
      },
      switched: function() {
        return !!user && !!user.switched;
      },
      notSwitched: function() {
        return !this.switched();
      }
    }
  }]);

  app.factory('Anime', ['$resource',
    function($resource){
      return $resource('/api/anime/:title/show', {}, {
        get: {method: 'GET'},
        save: {method: 'POST', url: '/api/anime/:title/edit', withCredentials: true},
        query: {method:'GET', url: '/api/anime', isArray: true},
        delete: {method: 'DELETE', url: '/api/anime/:title/delete', withCredentials: true}
      });
    }]);

  app.factory('Tag', ['$resource',
    function($resource){
      return $resource('/api/tag/:name/show', {}, {
        get: {method: 'GET'},
        save: {method: 'POST', url: '/api/tag/:name/edit', withCredentials: true},
        query: {method:'GET', url: '/api/tag', isArray: true},
        delete: {method: 'DELETE', url: '/api/tag/:name/delete', withCredentials: true}
      });
    }]);
})();