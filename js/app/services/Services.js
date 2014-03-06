'use strict';

/**
 * Services
 */
angular.module('App.Services', []);

module.exports = angular.module('App.Services')
    //Constants
    .constant('BaseUrlConst', require('./BaseUrlConst'))
    //Configuration Services
    .config(require('./RestangularConfig'))
    //Initialisation Services
    .run(require('./AuthenticationStateRun'))
    //Service Objects
    .service('CalculateServ', require('./CalculateServ'));