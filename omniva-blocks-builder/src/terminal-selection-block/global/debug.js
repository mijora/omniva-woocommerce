/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getOmnivaData } from './omniva';

/**
 * Variables
 */
const debug_enabled = getOmnivaData().debug;
const prefix_debug = 'OMNIVA BLOCKS DEBUG:';

/**
 * Export functions
 */
export const debug = (...variables) => {
    if ( ! debug_enabled ) {
        return;
    }
    
    console.log(prefix_debug, ...variables);
};

export const enableStateDebug = (stateName, stateValue) => {
    if ( ! debug_enabled ) {
        return;
    }

    useEffect(() => {
        console.log(prefix_debug, 'Changed state "' + stateName + '" value to:', stateValue);
    }, [
        stateValue
    ]);
};
