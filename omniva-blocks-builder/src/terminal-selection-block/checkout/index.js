/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit } from './edit';
import { omnivaVertical } from '../global/icons';

import metadata from './block.json';

registerBlockType(metadata, {
    icon: omnivaVertical,
    edit: Edit,
    attributes: {
        terminal: {
            type: 'string',
            default: '',
            source: 'attribute'
        }
    }
});
