'use strict';

/**
 * Password Match
 *
 * Example:
 * <form name="form">
 *     <input name="password" ng-model="user.password" />
 *     <input name="passwordConfirm" ng-model="user.passwordConfirm" password-match="user.password" />
 * </form>
 * <p ng-show="form.passwordConfirm.$error.passwordMatch">Passwords are not matched!</p>
 *
 * @param {string} passwordMatch Model property identifier to be "matched against"
 */
module.exports = [function () {
    
    return {
        restrict: 'A',
        require: 'ngModel',
        scope: false,
        link: function (scope, element, attributes, controller) {

            //watch the "matched against" model value, and set its passwordMatch validity conditional upon being identical to current model value
            //this executes when the "matched against" model value changes
            scope.$watch(attributes.passwordMatch, function (value) {
                controller.$setValidity('passwordMatch', value === controller.$viewValue);
            });

            //push a parsing pipe to the current model value, and set its passwordMatch validity conditional upon being identical to matched against model value
            //this executes when the current model value changes
            controller.$parsers.push(function (value) {
                var isValid = (value === scope.$eval(attributes.passwordMatch));
                controller.$setValidity('passwordMatch', isValid);
                return isValid ? value : undefined;
            });

        }
    };

}];