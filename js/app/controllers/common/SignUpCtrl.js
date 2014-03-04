'use strict';

/**
 * Sign Up Modal Controller
 */
module.exports = ['$scope', '$modalInstance', function ($scope, $modalInstance) {

    //default user object containing the email and password
    $scope.user = {};

    $scope.signup = function (user) {

        //user.username
        //user.email
        //user.password
        //
        //then call $modalInstance.close('logged_in');

    };

    $scope.cancel = function () {

        $modalInstance.dismiss('cancel');

    };

}];