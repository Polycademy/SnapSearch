'use strict';

/**
 * Calculate Service
 */
module.exports = [function () {

    /**
     * Rounds to the nearest place. It can be decimal place, or negative place.
     * 
     * @param  {string|integer|float} value  Number to be rounded
     * @param  {integer}              places Places can be positive or negative.
     * @return {integer|float}
     */
    this.round = function round(value, places) {

        if (typeof places === 'undefined' || +places === 0)
        return Math.round(value);

        value = +value;
        places  = +places;

        if (isNaN(value) || !(typeof places === 'number' && places % 1 === 0))
        return NaN;

        // Shift
        value = value.toString().split('e');
        value = Math.round(+(value[0] + 'e' + (value[1] ? (+value[1] + places) : places)));

        // Shift back
        value = value.toString().split('e');
        return +(value[0] + 'e' + (value[1] ? (+value[1] - places) : -places));

    };

}];