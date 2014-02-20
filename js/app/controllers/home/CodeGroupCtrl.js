'use strict';

module.exports = ['$scope', CodeGroupCtrl];

/**
 * Home Controller
 * 
 * @param {Object} $scope
 */
function CodeGroupCtrl ($scope) {

    $scope.activeCode = 'php';

    $scope.changeCode = function(value){
        $scope.activeCode = value;
    }

}