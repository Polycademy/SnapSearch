'use strict';

/**
 * User System Module
 */
angular.module('UserSystemMod', [])
    .provider('UserSystemServ', require('./UserSystemServ'))
    .run(require('./UserSystemRun'));

module.exports = angular.module('UserSystemMod');