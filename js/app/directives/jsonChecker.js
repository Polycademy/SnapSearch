'use strict';

module.exports = [function () {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, element, attributes, controller) {

            var isEmpty = function (value) {
                return angular.isUndefined(value) || value === '' || value === null;
            };

            var jsonValidator = function (value) {

                try {

                    //only if it's not empty
                    if (!isEmpty(value)) {
                        JSON.parse(value);
                    }
                    //empty values are valid, since we don't want to make the form field red immediately
                    controller.$setValidity('jsonChecker', true);
                    return value;
                
                } catch (e) {

                    controller.$setValidity('jsonChecker', false);
                    return undefined;

                }

            };

            controller.$parsers.push(jsonValidator);
            controller.$formatters.push(jsonValidator);

        }
    };
}];