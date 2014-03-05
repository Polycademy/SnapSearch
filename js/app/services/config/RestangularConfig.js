'use strict';

/**
 * Restangular Config Block
 */
module.exports = ['RestangularProvider', function (RestangularProvider) {

    RestangularProvider.setBaseUrl('/api');

    //restangular needs to unwrap our metadata
    RestangularProvider.setResponseExtractor(function(response, operation, what, url) {

        //this should only be done for requests to SnapSearch API
        console.log(what);
        console.log(url);

        var newResponse;

        if (operation === "getList") {

            newResponse = response.content;
            //this is not a status code! This is the status message
            newResponse.status = response.status;

        } else {

            newResponse = response.data;

        }

        return newResponse;

    });


}];