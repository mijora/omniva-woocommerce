/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import { txt } from './text';

const { omnivalt } = wcSettings.checkoutData.extensions;

export const getTerminalsByCountry = (country) => {
    return fetch(`${omnivalt.ajax_url}?action=omnivalt_get_terminals&country=${country}`, {
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
