'use strict';

var fs = require('fs');

/**
 * App Controller
 * 
 * @param {Object} $scope
 * @param {Object} $modal
 */
module.exports = ['$scope', '$modal', '$state', 'BusyLoopServ', 'UserSystemServ', function ($scope, $modal, $state, BusyLoopServ, UserSystemServ) {

    //it will check if the session is active every 20 seconds
    var cancelBusyLoop = BusyLoopServ(function () {
        UserSystemServ.getSession();
    }, 20000);

    $scope.$on('$destroy', function () {
        cancelBusyLoop();
    });

    $scope.modal = {};
    $scope.auth = {};

    /**
     * In the future, these 2 functions opening up the signup and login modal could be replaced with "modal" states that transition to and from the parent state which would whichever state that the person activated the modal box.
     * Watch: https://github.com/angular-ui/ui-router/issues/92
     * Then these states could be bound to a particular URL.
     * Also look into multiple inheritance of states.
     */

    $scope.modal.signUp = function () {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/signup_modal.html', 'utf8'), 
            controller: require('./SignUpModalCtrl'),
            windowClass: 'signup-modal form-modal'
        }).result.then(function () {
            $state.go('controlPanel');
        });

    };

    $scope.modal.logIn = function () {

        $modal.open({
            template: fs.readFileSync(__dirname + '/../../../templates/login_modal.html', 'utf8'),
            controller: require('./LogInModalCtrl'),
            windowClass: 'login-modal form-modal'
        }).result.then(function () {
            $state.go('controlPanel');
        });

    };

    $scope.auth.logOut = function () {

        UserSystemServ.logoutSession().then(function () {
            $state.go('home');
        });

    };

}];