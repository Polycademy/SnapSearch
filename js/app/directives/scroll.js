'use strict';

/**
 * Scroll Directive
 */
module.exports = ['$anchorScroll', '$location', function ($anchorScroll, $location) {

    return {
        link:  function (scope, element, attributes) {

            var scroll = function () {
                $location.hash(attributes.scroll);
                $anchorScroll();
                scope.$apply();
            };

            element.on('click', scroll);

            //we don't need to manually destroy as angular should handle direct element binding

        }
    };

}];