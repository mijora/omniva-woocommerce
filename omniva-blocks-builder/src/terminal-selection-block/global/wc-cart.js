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

export const getDestinationCountry = (shippingRates) => {
    debug('Getting destination country...');
    if ( ! shippingRates.length ) {
        debug('Destination country LT');
        return 'LT';
    }

    let country = '';
    for ( let i = 0; i < shippingRates.length; i++ ) {
        if ( ! shippingRates[i].destination.country || shippingRates[i].destination.country.trim() == "" ) {
            continue;
        }
        country = shippingRates[i].destination.country.trim();
    }
    
    debug('Destination country', country);
    return country;
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
