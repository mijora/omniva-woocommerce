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
import { getDestinationCountry, getActiveShippingRates } from './wc-cart';
import { isOmnivaTerminalMethod } from './omniva';
import { getTerminalsByCountry } from './terminals';
import { txt } from './text';

export const Block = ({ checkoutExtensionData, extensions }) => {
    const terminalValidationErrorId = 'omnivalt_terminal';
    const { setExtensionData } = checkoutExtensionData;
    const [terminals, setTerminals] = useState([
        {
            label: txt.select_terminal,
            value: '',
        }
    ]);
    const [showBlock, setShowBlock] = useState(false);
    const [selectedOmnivaTerminal, setSelectedOmnivaTerminal,] = useState('');
    const [selectedRateId, setSelectedRateId] = useState('');

    const debouncedSetExtensionData = useCallback(
        debounce((namespace, key, value) => {
            setExtensionData(namespace, key, value);
        }, 1000),
        [setExtensionData]
    );

    const { setValidationErrors, clearValidationError } = useDispatch(
        'wc/store/validation'
    );

    const validationError = useSelect((select) => {
        const store = select('wc/store/validation');
        return store.getValidationError(terminalValidationErrorId);
    });

    const shippingRates = useSelect((select) => {
        const store = select('wc/store/cart');
        return store.getCartData().shippingRates;
    });

    useEffect(() => {
        setShowBlock(false);
        if ( shippingRates.length ) {
            const activeRates = getActiveShippingRates(shippingRates);
            for ( let i = 0; i < activeRates.length; i++ ) {
                if ( ! activeRates[i].rate_id ) {
                    continue;
                }
                if ( isOmnivaTerminalMethod(activeRates[i].rate_id) && activeRates[i].selected ) {
                    setShowBlock(true);
                }
                if ( activeRates[i].selected ) {
                    setSelectedRateId(activeRates[i].rate_id);
                }
            }
        }
    }, [
        shippingRates
    ]);

    useEffect(() => {
        if ( showBlock ) {
            getTerminalsByCountry(getDestinationCountry(shippingRates)).then(terminals => {
                if ( terminals.data ) {
                    setTerminals(terminals.data);
                }
            });
        }
    }, [
        showBlock
    ]);

    /* Handle changing the select's value */
    useEffect(() => {
        setExtensionData(
            'omnivalt',
            'selected_terminal',
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

    useEffect(() => {
        setExtensionData(
            'omnivalt',
            'selected_rate_id',
            selectedRateId
        );
    }, [
        setExtensionData,
        selectedRateId
    ]);

    useEffect(()=>{ //TODO: laikinai
       console.log('Terminalas', selectedOmnivaTerminal);
    },[selectedOmnivaTerminal]);

    if ( ! showBlock ) {
        return <></>
    }

    return (
        <div className="omnivalt_terminal_select_container">
            <SelectControl
                label={txt.title_terminal}
                value={selectedOmnivaTerminal}
                options={terminals}
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
