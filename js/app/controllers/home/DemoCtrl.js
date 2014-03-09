'use strict';

var settings = require('../../Settings');

/**
 * Demo Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', 'Restangular', function ($scope, Restangular) {

    var demoUsername = settings.apiKeys.demo.user;
    var demoPassword = settings.apiKeys.demo.pass;

    /**
     * State to indicate requesting status.
     * 'never' => never requested
     * 'started' => started a request
     * 'finished' => finished request
     * 
     * @type {Number}
     */
    $scope.requestingDemoService = 'never';

    $scope.submit = function (demo) {

        $scope.formErrors = false;
        $scope.formSuccess = false;
        $scope.requestingDemoService = 'started';

        Restangular.all('demo').customGET('', {url: demo.url}).then(function (response) {

            console.log(response);
            $scope.formSuccess = true;

        }, function (response) {

            if (response.status === 400) {
                $scope.formErrors = response.data.content;
            } else if (response.status === 500) {
                $scope.formErrors = [];
                $scope.formErrors = $scope.formErrors.concat(
                    response.data.content.robotErrors, 
                    response.data.content.curlErrors
                ).filter(function (value) {
                    return value != undefined;
                });
            }

        })['finally'](function () {

            $scope.requestingDemoService = 'finished';

        });

        //demo.url


        //send request to the back end service
        //when the response comes back set $scope.requestingDemoService = 'finished';
        //set the content to $scope.demoServiceResponse = response.content;
        //set $scope.demoServiceResponseWithoutSnapSearch - with robots
        //set $scope.demoServiceResponseWithSnapSearch - standard curl

    };

}];