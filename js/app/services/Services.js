'use strict';

require('angular');

/**
 * Services
 */
angular.module('App.Services', []);

module.exports = angular.module('App.Services')
    //Initialisation Services
    .run(require('./AuthenticationState'))
    //Service Objects
    .service('Calculate', require('./Calculate'));