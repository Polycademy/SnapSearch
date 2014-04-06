'use strict';

var imagesloaded = require('imagesloaded');

/**
 * Asynchronous Anchor Scroll
 *
 * @param {string}  anchor      ID to scroll to
 * @param {integer} anchorDelay Delay in microseconds when scrolling
 * @param {string}  anchorEvent Event to listen to before scrolling
 */
module.exports = ['$location', '$anchorScroll', '$timeout', function ($location, $anchorScroll, $timeout) {

    return {
        link: function(scope, element, attributes){

            var id = attributes.anchor || attributes.id || attributes.name;
            var delay = attributes.anchorDelay;
            var eventName = attributes.anchorEvent;
            var firstTimeScrolling = true;

            element.attr('id', id);

            var scrollToHash = function(hash){

                if(id && hash && id === hash){

                    if(delay && firstTimeScrolling){

                        $timeout(function () {

                            imagesloaded(element, function () {
                                $anchorScroll();
                            });

                        }, delay);

                    }else{

                        imagesloaded(element, function () {
                            $anchorScroll();
                        });

                    }
                    
                    //only run the delay the first time this scrolling function executes
                    //if the hash didn't match, then this function didn't execute!
                    firstTimeScrolling = false;

                }

            };
            
            //listen for a custom event, useful if you're waiting on something else to be fully loaded as well
            if(eventName){

                scope.$on(eventName, function(){

                    scrollToHash($location.hash());

                });

            }

            //hash may be asynchronously changed, the directive may load before the hash is added
            scope.$watch(function(){

                return $location.hash();
            
            }, function(hash){

                scrollToHash(hash);

            });

        }
    };

}];