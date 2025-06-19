/**
 * Internal dependencies
 */
import { debug } from './debug';

/**
 * Export functions
 */
export const getOmnivaData = () => {
    if ( ! wcSettings || ! wcSettings["omnivalt-blocks_data"] ) {
        return [];
    }

    return wcSettings["omnivalt-blocks_data"];
};

export const getDynamicOmnivaData = (country, method) => {
    return fetch(`${getOmnivaData().ajax_url}?action=omnivalt_get_dynamic_data&country=${country}&method=${method}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error fetching terminals:', error);
        return [];
    });
};

export const isOmnivaMethod = (methodKey) => {
    if ( methodKey.startsWith("omnivalt") ) {
        return true;
    }

    return false;
};

export const isOmnivaTerminalMethod = (methodKey) => {
    for ( let [key, value] of Object.entries(getOmnivaData().methods) ) {
        if ( methodKey == value ) {
            debug('Detected Omniva method', value);
            return true;
        }
    }

    return false;
};
