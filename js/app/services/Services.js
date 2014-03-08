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
    .config(require('./UserSystemConfig'))
    //Initialisation Services
    .run(require('./AuthenticationStateRun'))
    //Service Objects
    .service('CalculateServ', require('./CalculateServ'));