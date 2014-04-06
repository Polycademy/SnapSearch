'use strict';

/**
 * Placeholder switch animation
 *
 * @param {array}   placeholderSwitch Model array of urls
 * @param {integer} placeholderDelay  Switch time in milliseconds
 */
module.exports = ['$interval', function ($interval) {

    return {
        link: function(scope, element, attributes){

            var delay;
            var interval;

            attributes.$observe('placeholderDelay', function (placeholderDelay) {

                if (!placeholderDelay) {
                    delay = 1000;
                } else {
                    delay = placeholderDelay;
                }

            });

            scope.$watch(attributes.placeholderSwitch, function (placeholders) {

                //any time the placeholders change, cancel the previous interval
                if (interval) {
                    $interval.cancel(interval);
                }

                if (placeholders) {

                    var index = 0;

                    attributes.$set('placeholder', placeholders[index]);

                    interval = $interval(function () {
                        index++;
                        if (index >= placeholders.length) {
                            index = 0;
                        }
                        attributes.$set('placeholder', placeholders[index]);
                    }, delay);

                }

            });

        }
    };

}];