'use strict';

module.exports = [function () {

    this.importScript = function (source, id, callback) {

        (function(d, s, id){
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)){ return; }
            js = d.createElement(s); js.id = id;
            if (callback) {
                js.onload = callback;
            }
            js.src = source;
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', id));

    };

}];