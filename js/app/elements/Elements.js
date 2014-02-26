'use strict';

require('angular');

/**
 * Elements
 *
 * It should be possible to require(Module).name instead of directly bringing in directives. This is because some reusable elements will become modules due to configuration or other things.
 */
angular.module('App.Elements', []);

module.exports = angular.module('App.Elements')
    .directive('syntax', require('./syntaxHighlight'));