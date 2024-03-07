/**
 * External dependencies
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import { SelectControl, TextareaControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { options } from './options';
import { txt } from './text';

export const Block = ({ checkoutExtensionData, extensions }) => {
	const { setExtensionData } = checkoutExtensionData;

	const debouncedSetExtensionData = useCallback(
		debounce((namespace, key, value) => {
			setExtensionData(namespace, key, value);
		}, 1000),
		[setExtensionData]
	);

	const terminalValidationErrorId = 'omnivalt_terminal';

	const { setValidationErrors, clearValidationError } = useDispatch(
		'wc/store/validation'
	);

	const validationError = useSelect((select) => {
		const store = select('wc/store/validation');

		return store.getValidationError(terminalValidationErrorId);
	});

	const [
		selectedOmnivaTerminal,
		setSelectedOmnivaTerminal,
	] = useState('');

	/* Handle changing the select's value */
	useEffect(() => {
		setExtensionData(
			'omnivalt',
			'alternateShippingInstruction',
			selectedOmnivaTerminal
		);

		if ( selectedOmnivaTerminal !== '' ) {
			clearValidationError(terminalValidationErrorId);
			return;
		}

		if ( selectedOmnivaTerminal === '' ) {
			setValidationErrors({
				[terminalValidationErrorId]: {
					message: txt.error_terminal,
					hidden: false
				}
			});
		}
	}, [
		setExtensionData,
		selectedOmnivaTerminal,
		setValidationErrors,
		clearValidationError,
	]);

	return (
		<div className="omnivalt_terminal_select_container">
			<SelectControl
				label={txt.title_terminal}
				value={selectedOmnivaTerminal}
				options={options}
				onChange={setSelectedOmnivaTerminal}
			/>
			{(validationError?.hidden || selectedOmnivaTerminal !== '') ? null : (
				<div className="wc-block-components-validation-error omnivalt-terminal-error">
					<span>{validationError?.message}</span>
				</div>
			)}
		</div>
	);
};
