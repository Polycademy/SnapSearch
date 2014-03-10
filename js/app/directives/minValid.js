'use strict';

module.exports = [function () {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, elem, attr, ctrl) {

            var isEmpty = function (value) {
                return angular.isUndefined(value) || value === '' || value === null || value !== value;
            };

            scope.$watch(attr.minValid, function(){
                ctrl.$setViewValue(ctrl.$viewValue);
            });

            var minValidator = function(value) {

                var min = scope.$eval(attr.minValid) || 0;
                if (!isEmpty(value) && value < min) {

                    ctrl.$setValidity('minValid', false);
                    return undefined;

                } else {

                    ctrl.$setValidity('minValid', true);
                    return value;

                }

            };

            ctrl.$parsers.push(minValidator);
            ctrl.$formatters.push(minValidator);

        }
    };
}];