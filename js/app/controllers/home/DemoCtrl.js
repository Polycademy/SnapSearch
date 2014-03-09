'use strict';

/**
 * Demo Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', 'Restangular', function ($scope, Restangular) {

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

            $scope.formSuccess = true;
            $scope.demoServiceResponse = response.content;

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

    };

}];