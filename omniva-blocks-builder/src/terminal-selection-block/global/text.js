/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const txt = wcSettings["omnivalt-blocks_data"].txt; //Temporary solution while not clear how use @wordpress/i18n

export const txt_json = {
    block_options: __('Block options', 'omnivalt'),
    title_terminal: __('Parcel terminal', 'omnivalt'),
    select_terminal: __('Select parcel terminal', 'omnivalt'),
    error_terminal: __('Please select parcel terminal', 'omnivalt'),
    cart_terminal_info: __('You can choose the parcel terminal on the Checkout page', 'omnivalt'),
    loading_field: __('Loading select field...', 'omnivalt'),
    title_post: __('Post office', 'omnivalt'),
    select_post: __('Select post office', 'omnivalt'),
    error_post: __('Please select post office', 'omnivalt'),
    cart_post_info: __('You can choose the post office on the Checkout page', 'omnivalt'),
    providers: {
        omniva: __('Omniva', 'omnivalt'),
        matkahuolto: __('Matkahuolto', 'omnivalt')
    },
    map: {
        modal_title_post: __('post offices', 'omnivalt'),
        modal_title_terminal: __('parcel terminals', 'omnivalt'),
        modal_search_title_post: __('Post offices list', 'omnivalt'),
        modal_search_title_terminal: __('Parcel terminals list', 'omnivalt'),
        select_post: __('Select post office', 'omnivalt'),
        select_terminal: __('Select terminal', 'omnivalt'),
        search_placeholder: __('Enter postcode', 'omnivalt'),
        search_button: __('Search', 'omnivalt'),
        select_button: __('Select', 'omnivalt'),
        modal_open_button: __('Select in map', 'omnivalt'),
        use_my_location: __('Use my location', 'omnivalt'),
        my_position: __('Distance calculated from this point', 'omnivalt'),
        not_found: __('Place not found', 'omnivalt'),
        no_cities_found: __('There were no cities found for your search term', 'omnivalt'),
        geo_not_supported: __('Geolocation is not supported', 'omnivalt')
    },
    select: {
        not_found: __('Place not found', 'omnivalt'),
        search_too_short: __('Value is too short', 'omnivalt'),
        terminal_select: __('Select terminal', 'omnivalt'),
        terminal_map_title: __('parcel terminals', 'omnivalt'),
        terminal_map_search_title: __('Parcel terminals addresses', 'omnivalt'),
        post_select: __('Select post office', 'omnivalt'),
        post_map_title: __('post offices', 'omnivalt'),
        post_map_search_title: __('Post offices addresses', 'omnivalt'),
        enter_address: __('Enter postcode/address', 'omnivalt'),
        show_in_map: __('Show in map', 'omnivalt'),
        show_more: __('Show more', 'omnivalt')
    }
};
