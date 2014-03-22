'use strict';

/**
 * Directives
 */
angular.module('App.Directives', []);

module.exports = angular.module('App.Directives')
    .directive('equaliseHeights', require('./equaliseHeights'))
    .directive('anchor', require('./anchor'))
    .directive('scroll', require('./scroll'))
    .directive('passwordMatch', require('./passwordMatch'))
    .directive('affix', require('./affix'))
    .directive('minValid', require('./minValid'))
    .directive('maxValid', require('./maxValid'))
    .directive('jsonChecker', require('./jsonChecker'));