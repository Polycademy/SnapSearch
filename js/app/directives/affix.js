'use strict';

var _ = require('lodash');

/**
 * Affix Directive
 *
 * Requires Lodash, jQuery
 */
module.exports = ['$window', '$document', function ($window, $document) {

    return {
        link: function (scope, element, attributes) {

                var win = angular.element($window),
                    doc  = angular.element($document),
                    affixed;

                var affixPosition = function () {

                    //default parameters of 0, it will always be fixed if 0
                    var offsetTop = scope.$eval(attributes.affixTop) || 0;
                    var offsetBottom = scope.$eval(attributes.affixBottom) || 0;

                    //default affix to false, which means set "affix" as class
                    //var affix = 'affix';
                    var affix;

                    //if the window scroll position is less or equal (above) the offsetTop, then set "affix-top"
                    //if the element offsetTop + element height is greater or equal (below) the document height - offsetBottom, then set "affix-bottom"
                    if (win.prop('pageYOffset') <= offsetTop) {
                        affix = 'affix-top';
                    } else if ((win.prop('pageYOffset') + element.outerHeight()) < (doc.height() - offsetBottom)) {
                        affix = 'affix';
                    } else if ((element.offset().top + element.outerHeight()) >= (doc.height() - offsetBottom)) {
                        affix = 'affix-bottom';
                    }

                    //currently once it has scrolled down, the position is fixed to the bottom, but it doesn't work when scrolling up

                    //if the same value, don't bother changing classes, because nothing changed
                    if(affixed === affix) return;

                    affixed = affix;

                    console.log(affix);

                    //reset the css classes and add either affix or affix-top or affix-bottom
                    element.removeClass('affix affix-top affix-bottom').addClass(affix);

                    //if affix was bottom, then pin it to where it currently is
                    if (affix === 'affix-bottom') {
                        element.offset({ top: doc.height() - offsetBottom - element.height() });
                    } else {
                        element.css('top', '');
                    }

                };

                var ensureWidth = function () {

                    element.css('width', element.parent().width());

                }

                var throttledAffix = _.throttle(affixPosition, 100);

                var throttledWidth = _.throttle(ensureWidth, 100);

                //when scrolling, we only have to figure out whether its affix, affix-top or affix-bottom
                win.bind('scroll', throttledAffix);

                //when resizing, we need to ensure the width and check the affix in case elements above pushed down this affixed element
                win.bind('resize', function () {
                    throttledAffix();
                    throttledWidth();
                });

                //run both at initialisation
                affixPosition();
                ensureWidth();

        }
    };

}];