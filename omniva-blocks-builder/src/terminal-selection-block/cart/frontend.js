import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import { Block } from './block';
import metadata from './block.json';

registerCheckoutBlock({
	metadata,
	component: Block
});
