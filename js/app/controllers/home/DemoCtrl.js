'use strict';

var settings = require('../../Settings');

/**
 * Demo Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

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

        $scope.requestingDemoService = 'started';

        //send request to the back end service
        //when the response comes back set $scope.requestingDemoService = 'finished';
        //set the content to $scope.demoServiceResponse = response.content;
        //set $scope.demoServiceResponseWithoutSnapSearch - with robots
        //set $scope.demoServiceResponseWithSnapSearch - standard curl

    };

}];