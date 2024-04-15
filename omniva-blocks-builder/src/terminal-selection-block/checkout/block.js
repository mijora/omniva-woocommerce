/**
 * External dependencies
 */
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { SelectControl, TextareaControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { getDestinationCountry, getActiveShippingRates } from '../global/wc-cart';
import { getOmnivaData, getDynamicOmnivaData, isOmnivaTerminalMethod } from '../global/omniva';
import { getTerminalsByCountry, loadMap, removeMap } from '../global/terminals';
import { txt } from '../global/text';
import { addTokenToValue, buildToken, isObjectEmpty, getObjectValue } from '../global/utils';
import { debug, enableStateDebug } from '../global/debug';

export const Block = ({ checkoutExtensionData, extensions }) => {
    const terminalValidationErrorId = 'omnivalt_terminal';
    const { setExtensionData } = checkoutExtensionData;
    const [country, setCountry] = useState('');
    const [activeRates, setActiveRates] = useState([]);
    const [omnivaData, setOmnivaData] = useState({});
    const [terminals, setTerminals] = useState([]);
    const [terminalsOptions, setTerminalsOptions] = useState([
        {
            label: txt.select_terminal,
            value: '',
        }
    ]);
    const [showBlock, setShowBlock] = useState(addTokenToValue(false)); //Need token to avoid undetected change when changing true>false>true in other useEffect functions
    const [blockText, setBlockText] = useState({
        title: txt.title_terminal,
        label: txt.select_terminal,
        error: txt.error_terminal,
    });
    const [selectedOmnivaTerminal, setSelectedOmnivaTerminal,] = useState('');
    const [selectedRateId, setSelectedRateId] = useState('');
    const [containerParams, setContainerParams] = useState({
        provider: 'unknown',
        type: 'unknown'
    })
    const elemTerminalSelectField = useRef(null);
    const elemMapContainer = useRef(null);
    const map = loadMap();

    enableStateDebug('Block show', showBlock);
    enableStateDebug('Terminals list', terminals);
    enableStateDebug('Selected country', country);
    enableStateDebug('Selected terminal', selectedOmnivaTerminal);
    enableStateDebug('Selected rate ID', selectedRateId);
    enableStateDebug('Omniva dynamic data', omnivaData);

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
        if ( shippingRates.length ) {
            setActiveRates(getActiveShippingRates(shippingRates));
        } else {
            debug('Empty param: shippingRates');
        }
    }, [
        shippingRates
    ]);

    useEffect(() => {
        setShowBlock(addTokenToValue(false));
        debug('Received ' + activeRates.length + ' active rates:', activeRates);
        for ( let i = 0; i < activeRates.length; i++ ) {
            if ( ! activeRates[i].rate_id ) {
                continue;
            }
            if ( ! activeRates[i].selected ) {
                continue;
            }
            if ( isOmnivaTerminalMethod(activeRates[i].rate_id) && activeRates[i].selected ) {
                setShowBlock(addTokenToValue(true));
            }
            setSelectedRateId(activeRates[i].rate_id);
        }
    }, [
        activeRates
    ]);

    useEffect(() => {
        if ( showBlock.value ) {
            setCountry(getDestinationCountry(shippingRates));
        }
    }, [
        showBlock
    ]);

    useEffect(() => {
        if ( ! isOmnivaTerminalMethod(selectedRateId) ) {
            debug('The selected delivery method is not delivery to the Omniva terminal');
            return;
        }
        if ( ! selectedRateId ) {
            debug('Skipped retrieving dynamic Omniva data because the value of the selected rate ID is not received');
            return;
        }
        if ( country == '' ) {
            debug('Skipped retrieving dynamic Omniva data because the value of the country is empty');
            return;
        }

        getDynamicOmnivaData(country, selectedRateId).then(response => {
            if ( response.data ) {
                const data = response.data;
                data.country = country;
                setOmnivaData(data);
            } else {
                debug('Failed to get dynamic Omniva data');
            }
        });
    }, [
        country,
        selectedRateId
    ]);

    useEffect(() => {
        if ( isObjectEmpty(omnivaData) ) {
            debug('Skipped getting Terminals because the Omniva dynamic data is empty');
            return;
        }
        if ( omnivaData.terminals_type === false ) {
            debug('The selected delivery method does not have a terminals');
            return;
        }

        const terminalsType = ('terminals_type' in omnivaData) ? omnivaData.terminals_type : 'omniva';
        getTerminalsByCountry(country, terminalsType).then(response => {
            if ( response.data ) {
                setTerminals(response.data);
            } else {
                debug('Failed to get terminals list');
            }
        });
    }, [
        omnivaData
    ]);

    useEffect(() => {
        const text_obj = {
            title: txt.title_terminal,
            label: txt.select_terminal,
            error: txt.error_terminal,
        };
        if ( ! isObjectEmpty(omnivaData) && ('terminals_type' in omnivaData) ) {
            if ( omnivaData.terminals_type == 'post' ) {
                text_obj.title = txt.title_post;
                text_obj.label = txt.select_post;
                text_obj.error = txt.error_post;
            }
        }
        setBlockText(text_obj);
    }, [
        omnivaData
    ]);

    useEffect(() => {
        if ( ! terminals.length ) {
            debug('Skipped updating terminals block because the terminals list is empty');
            return;
        }

        debug('Updating terminal selection options...');
        const preparedTerminalsOptions = [
            {
                label: blockText.label,
                value: '',
            }
        ];
        for ( let i = 0; i < terminals.length; i++ ) {
            preparedTerminalsOptions.push({
                label: terminals[i].name,
                value: terminals[i].id
            });
        }
        setTerminalsOptions(preparedTerminalsOptions);

        if ( elemMapContainer.current ) {
            if ( elemMapContainer.current.innerHTML !== "" ) {
                debug('Removing previous map...');
                removeMap(elemMapContainer.current);
            }
            let showMap = null;
            if ( 'show_map' in getOmnivaData() ) {
                showMap = getOmnivaData().show_map;
                setContainerParams({
                    provider: ('provider' in omnivaData) ? omnivaData.provider : 'unknown',
                    type: ('terminals_type' in omnivaData) ? omnivaData.terminals_type : 'unknown'
                });
            }
            if ( showMap === true ) {
                debug('Initializing TMJS map...');
                map.load_data({
                    org_field: elemTerminalSelectField.current,
                    map_container: elemMapContainer.current,
                    terminals_type: omnivaData.terminals_type,
                    provider: omnivaData.provider,
                    country: country,
                    map_icon: omnivaData.map_icon,
                    selected_terminal: selectedOmnivaTerminal
                });
                map.init(terminals);
            } else if ( showMap === false ) {
console.log('Nerodo zemes'); //TODO: Padaryti custom select field
            } else {
                debug('Failed to get map display param');
            }
        } else {
            debug('Failed to get map container');
        }
    }, [
        terminals
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
                    message: blockText.error,
                    hidden: false
                }
            });
        }
    }, [
        setExtensionData,
        selectedOmnivaTerminal,
        setValidationErrors,
        clearValidationError,
        blockText
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

    if ( ! showBlock.value ) {
        return <></>
    }

    return (
        <div className={`omnivalt-terminal-select-container provider-${containerParams.provider} type-${containerParams.type}`}>
            <div id="omnivalt-terminal-container-org" className="omnivalt-org-select">
                <SelectControl
                    id="omnivalt-terminal-select-field"
                    label={blockText.title}
                    value={selectedOmnivaTerminal}
                    options={terminalsOptions}
                    onChange={(value) => setSelectedOmnivaTerminal(value)}
                    ref={elemTerminalSelectField}
                />
                {(validationError?.hidden || selectedOmnivaTerminal !== '') ? null : (
                    <div className="wc-block-components-validation-error omnivalt-terminal-error">
                        <span>{validationError?.message}</span>
                    </div>
                )}
            </div>
            <div id="omnivalt-terminal-container-map" className="omnivalt-map-select" ref={elemMapContainer}></div>
        </div>
    );
};
