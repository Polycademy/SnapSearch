'use strict';

var fs = require('fs');

/**
 * Header Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', '$modal', function ($scope, $modal) {

    /**
     * In the future, these 2 functions opening up the signup and login modal could be replaced with "modal" states that transition to and from the parent state which would whichever state that the person activated the modal box.
     * Watch: https://github.com/angular-ui/ui-router/issues/92
     * Then these states could be bound to a particular URL.
     * Also look into multiple inheritance of states.
     */

    $scope.openSignUpModal = function () {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/signup.html', 'utf8'), 
            controller: require('./SignUpCtrl')
        }).result.then(function () {

            console.log('logged in!');

            //successfully signed in, should autologin

        }, function () {

            console.log('cancelled');

            //fail modal

        });

    };

    $scope.openLogInModal = function () {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/login.html', 'utf8'),
            controller: require('./LogInCtrl')
        }).result.then(function () {

            //successfully logged in

        }, function () {

            //closed the modal

        });

    };

    $scope.logout = function () {

        //just log the person out

    };

}];