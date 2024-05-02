/**
 * Internal dependencies
 */
import { debug } from './debug';

/**
 * Export functions
 */
export const getShippingCountry = (shippingAddress) => {
    debug('Getting shipping country...');
    if ( shippingAddress.country.trim() == "" ) {
        debug('Shipping country LT');
        return 'LT';
    }

    debug('Shipping country', shippingAddress.country);
    return shippingAddress.country;
};

export const getDestination = (shippingRates, getFirst = true) => {
    debug('Getting destination...');
    if ( ! shippingRates.length ) {
        debug('Failed to get destination because shipping rates is empty');
        return null;
    }

    let allDestinations = [];
    for ( let i = 0; i < shippingRates.length; i++ ) {
        if ( ! shippingRates[i].destination ) {
            continue;
        }
        allDestinations.push(shippingRates[i].destination);
    }

    if ( ! allDestinations.length ) {
        debug('Failed to get destination');
        return null;
    }

    if ( ! getFirst ) {
        debug('Destinations', allDestinations);
        return allDestinations;
    }

    debug('First destination', allDestinations[0]);
    return allDestinations[0];
};

export const getActiveShippingRates = (shippingRates) => {
    if ( ! shippingRates.length ) {
        return [];
    }

    let activeRates = [];
    for ( let i = 0; i < shippingRates.length; i++ ) {
        if ( ! shippingRates[i].shipping_rates ) {
            continue;
        }
        for ( let j = 0; j < shippingRates[i].shipping_rates.length; j++ ) {
            activeRates.push(shippingRates[i].shipping_rates[j]);
        }
    }
    
    return activeRates;
};
