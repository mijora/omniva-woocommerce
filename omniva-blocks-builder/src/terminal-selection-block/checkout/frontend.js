import { registerCheckoutBlock, getRegisteredBlocks } from '@woocommerce/blocks-checkout';

import { Block } from './block';
import metadata from './block.json';
import './frontend.scss';

registerCheckoutBlock({
	metadata,
	component: Block,
});
