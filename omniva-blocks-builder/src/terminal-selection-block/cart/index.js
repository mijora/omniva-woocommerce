/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { omnivaVertical } from '../global/icons';

import metadata from './block.json';

registerBlockType(metadata, {
    icon: omnivaVertical,
    edit: Edit,
    save: Save
});
