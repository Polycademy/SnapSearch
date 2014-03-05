'use strict';

require('angular');

/**
 * Services
 */
angular.module('App.Services', []);

module.exports = angular.module('App.Services')
    //Configuration Services
    .config(require('./config/RestangularConfig'))
    //Initialisation Services
    .run(require('./run/AuthenticationState'))
    //Service Objects
    .service('Calculate', require('./Calculate'));