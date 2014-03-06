'use strict';

/**
 * User State Service Provider
 */
module.exports = function () {

    var userData = {},
        originalDestination = '',
        authGateway = '/';

    this.setAuthGateway = function (path) {
        authGateway = path;
    };


};