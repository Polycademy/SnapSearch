'use strict';

/**
 * Login Modal Controller
 */
module.exports = ['$scope', '$modalInstance', 'Restangular', function ($scope, $modalInstance, Restangular) {

    //default user object containing the email and password
    $scope.user = {};

    $scope.formErrors = false;
    $scope.formSuccess = false;

    $scope.login = function (user) {

        //user.email
        //user.password
        //
        //then call $modalInstance.close('logged_in');

    };

    $scope.cancel = function () {

        $modalInstance.dismiss('cancel');

    };

}];