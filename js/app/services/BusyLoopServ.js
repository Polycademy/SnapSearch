'use strict';

module.exports = ['$timeout', function ($timeout) {

    return function (callback, delay, invokeApply) {

        //default delay is 0
        delay = delay || 0;
        //default invokeApply is true
        invokeApply = typeof invokeApply !== 'undefined' ? invokeApply : true;

        var timer;

        var loop = function () {
            //replace the timer promise
            timer = $timeout(function () {
                callback();
                loop();
            }, delay, invokeApply);
        };

        loop();

        return function () {
            loop = angular.noop;
            $timeout.cancel(timer);
        };

    };

}];