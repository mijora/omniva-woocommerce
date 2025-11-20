/**
 * External dependencies
 */
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { SelectControl, TextareaControl, Icon } from '@wordpress/components';
import { warning } from '@wordpress/icons';
import { useSelect, useDispatch } from '@wordpress/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { getDestination, getActiveShippingRates } from '../global/wc-cart';
import { getOmnivaData, getDynamicOmnivaData, isOmnivaMethod, isOmnivaTerminalMethod } from '../global/omniva';
import { getTerminalsByCountry, loadMap, removeMap, loadCustomSelect } from '../global/terminals';
import { txt } from '../global/text';
import { addTokenToValue, isObjectEmpty, findArrayElemByObjProp} from '../global/utils';
import { debug, enableStateDebug } from '../global/debug';

export const Block = ({ checkoutExtensionData, extensions }) => {
    const terminalValidationErrorId = 'omnivalt_terminal';
    const phoneValidationErrorId = 'shipping_phone';
    const { setExtensionData } = checkoutExtensionData;
    const [mapValues, setMapValues] = useState({
        country: 'LT',
        postcode: ''
    });
    const [destination, setDestination] = useState(null);
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
    const [selectedOmnivaTerminal, setSelectedOmnivaTerminal] = useState('');
    const [selectedRateId, setSelectedRateId] = useState('');
    const [containerParams, setContainerParams] = useState({
        provider: 'unknown',
        type: 'unknown',
    });
    const [containerErrorClass, setContainerErrorClass] = useState('');
    const elemTerminalSelectField = useRef(null);
    const elemMapContainer = useRef(null);
    const map = loadMap();
    const customSelect = loadCustomSelect();

    enableStateDebug('Block show', showBlock);
    enableStateDebug('Terminals list', terminals);
    enableStateDebug('Destination', destination);
    enableStateDebug('Map values', mapValues);
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

    const terminalValidationError = useSelect((select) => {
        const storeValidation = select('wc/store/validation');
        return storeValidation.getValidationError(terminalValidationErrorId);
    });

    const phoneValidationError = useSelect((select) => {
        const storeValidation = select('wc/store/validation');
        return storeValidation.getValidationError(phoneValidationErrorId);
    });

    const shippingRates = useSelect((select) => {
        const storeCart = select('wc/store/cart');
        return storeCart.getCartData().shippingRates;
    });

    const customerData = useSelect((select) => {
        const storeCart = select('wc/store/cart');
        const billingAddress = storeCart.getCartData().billingAddress;
        const shippingAddress = storeCart.getCartData().shippingAddress;
        
        const phone = ((shippingAddress.phone && shippingAddress.phone.trim() !== '') ? shippingAddress.phone : billingAddress.phone) || '';
        
        return {
            phone: phone,
            country: shippingAddress.country
        };
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
        debug('Updating destination...');
        setDestination(getDestination(shippingRates));
    }, [
        activeRates
    ]);

    useEffect(() => {
        if ( ! destination || destination.country.trim() == "" ) {
            setMapValues({
                country: 'LT',
                postcode: ''
            });
        } else {
            setMapValues({
                country: destination.country,
                postcode: destination.postcode
            });
        }
    }, [
        destination
    ]);

    useEffect(() => {
        if ( ! selectedRateId ) {
            debug('Skipped retrieving dynamic Omniva data because the value of the selected rate ID is not received');
            return;
        }
        if ( ! destination ) {
            debug('Skipped retrieving dynamic Omniva data because the destination is still empty');
            return;
        }

        getDynamicOmnivaData(destination.country, selectedRateId).then(response => {
            if ( response.data ) {
                const data = response.data;
                debug('Updating Omniva data...', data);
                setOmnivaData(data);
            } else {
                debug('Failed to get dynamic Omniva data');
            }
        });
    }, [
        destination,
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
        getTerminalsByCountry(mapValues.country, terminalsType).then(response => {
            if ( response.data ) {
                debug('Updating terminals list...');
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

        if ( selectedOmnivaTerminal !== '' ) {
            debug('Checking if the selected terminal is in the terminals list...');
            if ( ! findArrayElemByObjProp(terminals, 'id', selectedOmnivaTerminal) ) {
                debug('The specified terminal was not found in the list of terminals');
                setSelectedOmnivaTerminal('');
            }
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
    }, [
        terminals
    ]);

    useEffect(() => {
        if ( elemMapContainer.current ) {
            if ( elemMapContainer.current.innerHTML !== "" ) {
                debug('Removing previous custom field/map...');
                removeMap(elemMapContainer.current);
            }
            let showMap = null;
            let autoselect = true;
            if ( 'show_map' in getOmnivaData() ) {
                showMap = getOmnivaData().show_map;
                autoselect = getOmnivaData().autoselect;
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
                    country: mapValues.country,
                    map_icon: omnivaData.map_icon,
                    selected_terminal: selectedOmnivaTerminal,
                    autoselect: autoselect
                });
                map.init(terminals);
                map.set_search_value(mapValues.postcode);
                map.activate_autoselect();
            } else if ( showMap === false ) {
                debug('Initializing terminal select field...');
                customSelect.load_data({
                    org_field: elemTerminalSelectField.current,
                    custom_container: elemMapContainer.current,
                    provider: omnivaData.provider,
                    terminals_type: omnivaData.terminals_type,
                    country: mapValues.country,
                    selected_terminal: selectedOmnivaTerminal,
                    autoselect_terminal: autoselect
                });
                customSelect.set_terminals(terminals);
                customSelect.init();
                customSelect.set_search_value(mapValues.postcode);
            } else {
                debug('Failed to get map display param');
            }
        } else {
            debug('Failed to get container for custom field/map');
        }
    }, [
        terminalsOptions
    ]);

    /* Validate phone */
    useEffect(() => {
        clearValidationError(phoneValidationErrorId);

        if (!isOmnivaMethod(selectedRateId)) {
            return;
        }

        if (!omnivaData.phone_regex || omnivaData.phone_regex.trim() === '') {
            debug('Phone regex empty. Skipped validation.');
            return;
        }

        const regex = new RegExp(omnivaData.phone_regex);

        if (!regex.test(customerData.phone)) {
            debug('Incorrect phone number. Regex: ' + omnivaData.phone_regex);
            setValidationErrors({
                [phoneValidationErrorId]: {
                    message: txt.errors.invalid_format,
                    hidden: false
                }
            });
        } else {
            debug('Phone number is correct');
        }
    }, [
        customerData,
        omnivaData,
        selectedRateId
    ]);

    /* Handle changing the select's value */
    useEffect(() => {
        if ( terminalValidationError ) {
            debug('Clearing terminal validation error...');
            clearValidationError(terminalValidationErrorId);
            setContainerErrorClass('');
        }

        if ( ! isOmnivaTerminalMethod(selectedRateId) ) {
            return;
        }

        setExtensionData(
            'omnivalt',
            'selected_terminal',
            selectedOmnivaTerminal
        );

        if ( selectedOmnivaTerminal === '' ) {
            debug('Terminal not selected. Adding terminal validation error...');
            setValidationErrors({
                [terminalValidationErrorId]: {
                    message: blockText.error,
                    hidden: false
                }
            });
            setContainerErrorClass('error');
        }
    }, [
        setExtensionData,
        selectedOmnivaTerminal,
        selectedRateId
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

    if ( ! isOmnivaMethod(selectedRateId) ) {
        return null;
    }

    return (
        <div className="omnivalt-container">
            <div className="omnivalt-general-errors">
                {(phoneValidationError && !phoneValidationError.hidden) && (
                    <div className="wc-block-components-validation-error omnivalt-phone-error">
                        <Icon icon={warning} size={20} className="omnivalt-error-icon" />
                        <span>{txt.errors.invalid_phone_format}</span>
                    </div>
                )}
            </div>
            {showBlock.value && (
                <div className={`omnivalt-terminal-select-container provider-${containerParams.provider} type-${containerParams.type} ${containerErrorClass}`}>
                    <div id="omnivalt-terminal-container-org" className="omnivalt-org-select">
                        <SelectControl
                            id="omnivalt-terminal-select-field"
                            label={blockText.title}
                            value={selectedOmnivaTerminal}
                            options={terminalsOptions}
                            onChange={(value) => setSelectedOmnivaTerminal(value)}
                            ref={elemTerminalSelectField}
                        />
                        {(terminalValidationError?.hidden || selectedOmnivaTerminal !== '') ? null : (
                            <div className="wc-block-components-validation-error omnivalt-terminal-error">
                                <Icon icon={warning} size={20} className="omnivalt-error-icon" />
                                <span>{terminalValidationError?.message}</span>
                            </div>
                        )}
                    </div>
                    <div id="omnivalt-terminal-container-map" className="omnivalt-map-select" ref={elemMapContainer}><div class="omnivalt-loader"></div></div>
                </div>
            )}
        </div>
    );
};
