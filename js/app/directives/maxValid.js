'use strict';

module.exports = [function () {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, elem, attr, ctrl) {

            var isEmpty = function (value) {
                return angular.isUndefined(value) || value === '' || value === null || value !== value;
            };

            scope.$watch(attr.maxValid, function(){
                ctrl.$setViewValue(ctrl.$viewValue);
            });

            var maxValidator = function(value) {

                var max = scope.$eval(attr.maxValid) || Infinity;
                if (!isEmpty(value) && value > max) {

                    ctrl.$setValidity('maxValid', false);
                    return undefined;

                } else {

                    ctrl.$setValidity('maxValid', true);
                    return value;

                }

            };

            ctrl.$parsers.push(maxValidator);
            ctrl.$formatters.push(maxValidator);

        }
    };
}];