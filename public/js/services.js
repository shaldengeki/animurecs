(function() {
  var app = angular.module('animurecsServices', ['ngResource']);

  app.factory('User', ['$resource',
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
        }
      });
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