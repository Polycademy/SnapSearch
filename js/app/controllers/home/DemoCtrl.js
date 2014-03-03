'use strict';

var config = require('../../Config.js');

/**
 * Demo Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

    var demoUsername = config.apiKeys.demo.user;
    var demoPassword = config.apiKeys.demo.pass;

    /**
     * State to indicate requesting status.
     * 'never' => never requested
     * 'started' => started a request
     * 'finished' => finished request
     * 
     * @type {Number}
     */
    $scope.requestingDemoService = 'never';

    $scope.validateUrl = function (url) {

        var regex = /^(http|https):\/\/(([a-zA-Z0-9$\-_.+!*'(),;:&=]|%[0-9a-fA-F]{2})+@)?(((25[0-5]|2[0-4][0-9]|[0-1][0-9][0-9]|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9][0-9]|[1-9][0-9]|[0-9])){3})|localhost|([a-zA-Z0-9\-\u00C0-\u017F]+\.)+([a-zA-Z]{2,}))(:[0-9]+)?(\/(([a-zA-Z0-9$\-_.+!*'(),;:@&=]|%[0-9a-fA-F]{2})*(\/([a-zA-Z0-9$\-_.+!*'(),;:@&=]|%[0-9a-fA-F]{2})*)*)?(\?([a-zA-Z0-9$\-_.+!*'(),;:@&=\/?]|%[0-9a-fA-F]{2})*)?(\#([a-zA-Z0-9$\-_.+!*'(),;:@&=\/?]|%[0-9a-fA-F]{2})*)?)?$/;

        //this boolean is going to be flipped by ui-validate (https://github.com/angular-ui/ui-utils/issues/193)
        return regex.test(url);

    };

    $scope.submit = function (demo) {

        $scope.requestingDemoService = 'started';

        //send request to the back end service
        //when the response comes back set $scope.requestingDemoService = 'finished';
        //set the content to $scope.demoServiceResponse = response.content;
        //set $scope.demoServiceResponseWithoutSnapSearch - with robots
        //set $scope.demoServiceResponseWithSnapSearch - standard curl

    };

}];