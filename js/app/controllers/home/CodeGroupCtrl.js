'use strict';

/**
 * Code Group Controller
 * Controls the code group allowing the ability to switch the code examples.
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

    $scope.activeCode = 'php';

    $scope.changeCode = function(value){
        $scope.activeCode = value;
    }

}];