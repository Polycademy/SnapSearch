'use strict';

/**
 * User State Module
 */
angular.module('UserStateMod', [])
    .provider('UserStateServ', require('./UserStateServ'))
    .run(require('./UserStateRun'));

module.exports = angular.module('UserStateMod');