'use strict';

/**
 * Restangular Config Block
 */
module.exports = ['RestangularProvider', 'BaseUrlConst', function (RestangularProvider, BaseUrlConst) {

    //trim the base url of slashes if they exist
    BaseUrlConst = BaseUrlConst.replace(new RegExp('/' + '*$'), '');

    RestangularProvider.setBaseUrl(BaseUrlConst + '/api');

}];