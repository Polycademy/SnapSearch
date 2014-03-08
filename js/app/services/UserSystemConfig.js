'use strict';

/**
 * User System Config Block
 */
module.exports = ['UserSystemServProvider', function (UserSystemServProvider) {

    UserSystemServProvider.setAccountsResource('accounts');
    UserSystemServProvider.setSessionResource('session');

}];