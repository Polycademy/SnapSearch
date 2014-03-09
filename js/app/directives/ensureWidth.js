'use strict';

var _ = require('lodash');

module.exports = ['$window', function ($window) {

    return {
        link: function (scope, element, attributes) {

            var ensureWidth = function () {
                element.css('width', element.parent().width());
            };

            var throttledWidth = _.throttle(ensureWidth, 100);

            angular.element($window).bind('resize', throttledWidth);

            ensureWidth();

        }
    };

}];