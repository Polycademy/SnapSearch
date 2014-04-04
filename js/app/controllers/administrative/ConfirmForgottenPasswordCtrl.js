'use strict';

/**
 * Confirm Forgotten Password Controller
 * This is where the person arrives once they get the password.
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', '$state', '$stateParams', '$timeout', 'Restangular', function ($scope, $state, $stateParams, $timeout, Restangular) {

    var userId = $stateParams.user_id;
    var forgottenCode = $stateParams.forgotten_code;

    $scope.showForm = true;
    if (!forgottenCode || !userId) {
        $scope.showForm = false;
    }

    $scope.user = {};

    $scope.changePassword = function (user) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        Restangular.all('accounts/confirm_forgotten_password').post({
            userId: userId,
            forgottenCode: forgottenCode,
            newPassword: user.newPassword
        }).then(function (response) {

            $scope.formSuccess = 'Successfully Changed Password.'
            $timeout(function () {
                $state.go('home');
            }, 1500);

        }, function (response) {

            if (typeof response.data.content == 'string') {
                $scope.formErrors = [response.data.content];
            } else {
                $scope.formErrors = response.data.content;
            }

        });

    };



}];