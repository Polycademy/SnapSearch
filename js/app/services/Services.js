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
    // .run(require('./RestangularXSRF')) // doesn't yet work, need cookies in response interception
    //Service Objects
    .service('CalculateServ', require('./CalculateServ'))
    .factory('MomentServ', require('./MomentServ'));