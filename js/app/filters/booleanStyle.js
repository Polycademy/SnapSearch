'use strict';

/**
 * Boolean style filter.
 * It will convert boolean style inputs into truthy of falsey values.
 * By default it will just convert them into boolean true/false.
 */
module.exports = [function () {

    return function (input, trueValue, falseValue) {

        trueValue = (typeof trueValue === 'undefined') ? true : trueValue;
        falseValue = (typeof falseValue === 'undefined') ? false : falseValue;

        var output;

        switch(input){
            case true:
            case 'true':
            case 1:
            case '1':
            case 'on':
            case 'yes':
                output = trueValue;
                break;
            case false:
            case 'false':
            case 0:
            case '0':
            case 'off':
            case 'no':
                output = falseValue;
                break;
            default:
                output = falseValue;
                break;
        }
        
        return output;

    };

}];