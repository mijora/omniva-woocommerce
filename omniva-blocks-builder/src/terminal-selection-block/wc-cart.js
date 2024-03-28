/**
 * Export functions
 */
export const getShippingCountry = (shippingAddress) => {
    if ( shippingAddress.country.trim() == "" ) {
        return 'LT';
    }
    return shippingAddress.country;
};

export const getDestinationCountry = (shippingRates) => {
    if ( ! shippingRates.length ) {
        return 'LT';
    }

    let country = '';
    for ( let i = 0; i < shippingRates.length; i++ ) {
        if ( ! shippingRates[i].destination.country || shippingRates[i].destination.country.trim() == "" ) {
            continue;
        }
        country = shippingRates[i].destination.country.trim();
    }
    
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
