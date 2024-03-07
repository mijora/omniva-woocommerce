/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, Disabled } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
//import './style.scss';
import { options } from './options';
import { txt } from './text';

//const { defaultShippingText } = getSetting('shipping-workshop_data', '');
//const { defaultShippingText } = "Testas";

export const Edit = ({ attributes, setAttributes }) => {
	const { text } = attributes;
	const blockProps = useBlockProps();
	return (
		<div {...blockProps} style={{ display: 'block' }}>
			<InspectorControls>
				<PanelBody title={txt.block_options}>
					Options for the block go here.
				</PanelBody>
			</InspectorControls>
			<div>
				<RichText
					value={text || txt.title_terminal}
					onChange={(value) => setAttributes({ text: value })}
				/>
			</div>
			<div>
				<Disabled>
					<SelectControl options={options} />
				</Disabled>
			</div>
		</div>
	);
};

export const Save = ({ attributes }) => {
	const { text } = attributes;
	return (
		<div {...useBlockProps.save()}>
			<RichText.Content value={text} />
		</div>
	);
};
