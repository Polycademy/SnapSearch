'use strict';

/**
 * Scroll Directive
 */
module.exports = ['$anchorScroll', '$location', function ($anchorScroll, $location) {

    return {
        link:  function (scope, element, attributes) {

            $location.hash(attributes.scroll);
            $anchorScroll();

        }
    };

}];