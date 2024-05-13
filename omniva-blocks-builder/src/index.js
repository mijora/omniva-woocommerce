/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin('omnivalt', {
	render,
	scope: 'woocommerce-checkout',
});
