/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/terminal-selection-block/checkout/block.js":
/*!********************************************************!*\
  !*** ./src/terminal-selection-block/checkout/block.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Block: () => (/* binding */ Block)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _global_wc_cart__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../global/wc-cart */ "./src/terminal-selection-block/global/wc-cart.js");
/* harmony import */ var _global_omniva__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../global/omniva */ "./src/terminal-selection-block/global/omniva.js");
/* harmony import */ var _global_terminals__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../global/terminals */ "./src/terminal-selection-block/global/terminals.js");
/* harmony import */ var _global_text__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../global/text */ "./src/terminal-selection-block/global/text.js");
/* harmony import */ var _global_utils__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../global/utils */ "./src/terminal-selection-block/global/utils.js");
/* harmony import */ var _global_debug__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../global/debug */ "./src/terminal-selection-block/global/debug.js");

/**
 * External dependencies
 */





/**
 * Internal dependencies
 */






const Block = ({
  checkoutExtensionData,
  extensions
}) => {
  const terminalValidationErrorId = 'omnivalt_terminal';
  const {
    setExtensionData
  } = checkoutExtensionData;
  const [mapValues, setMapValues] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    country: 'LT',
    postcode: ''
  });
  const [destination, setDestination] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [activeRates, setActiveRates] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [omnivaData, setOmnivaData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [terminals, setTerminals] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [terminalsOptions, setTerminalsOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([{
    label: _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.select_terminal,
    value: ''
  }]);
  const [showBlock, setShowBlock] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)((0,_global_utils__WEBPACK_IMPORTED_MODULE_9__.addTokenToValue)(false)); //Need token to avoid undetected change when changing true>false>true in other useEffect functions
  const [blockText, setBlockText] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    title: _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.title_terminal,
    label: _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.select_terminal,
    error: _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.error_terminal
  });
  const [selectedOmnivaTerminal, setSelectedOmnivaTerminal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selectedRateId, setSelectedRateId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [containerParams, setContainerParams] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    provider: 'unknown',
    type: 'unknown'
  });
  const [containerErrorClass, setContainerErrorClass] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const elemTerminalSelectField = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const elemMapContainer = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const map = (0,_global_terminals__WEBPACK_IMPORTED_MODULE_7__.loadMap)();
  const customSelect = (0,_global_terminals__WEBPACK_IMPORTED_MODULE_7__.loadCustomSelect)();
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Block show', showBlock);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Terminals list', terminals);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Destination', destination);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Map values', mapValues);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Selected terminal', selectedOmnivaTerminal);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Selected rate ID', selectedRateId);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Omniva dynamic data', omnivaData);
  const debouncedSetExtensionData = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useCallback)((0,lodash__WEBPACK_IMPORTED_MODULE_4__.debounce)((namespace, key, value) => {
    setExtensionData(namespace, key, value);
  }, 1000), [setExtensionData]);
  const {
    setValidationErrors,
    clearValidationError
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useDispatch)('wc/store/validation');
  const validationError = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useSelect)(select => {
    const store = select('wc/store/validation');
    return store.getValidationError(terminalValidationErrorId);
  });
  const shippingRates = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useSelect)(select => {
    const store = select('wc/store/cart');
    return store.getCartData().shippingRates;
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (shippingRates.length) {
      setActiveRates((0,_global_wc_cart__WEBPACK_IMPORTED_MODULE_5__.getActiveShippingRates)(shippingRates));
    } else {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Empty param: shippingRates');
    }
  }, [shippingRates]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setShowBlock((0,_global_utils__WEBPACK_IMPORTED_MODULE_9__.addTokenToValue)(false));
    (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Received ' + activeRates.length + ' active rates:', activeRates);
    for (let i = 0; i < activeRates.length; i++) {
      if (!activeRates[i].rate_id) {
        continue;
      }
      if (!activeRates[i].selected) {
        continue;
      }
      if ((0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.isOmnivaTerminalMethod)(activeRates[i].rate_id) && activeRates[i].selected) {
        setShowBlock((0,_global_utils__WEBPACK_IMPORTED_MODULE_9__.addTokenToValue)(true));
      }
      setSelectedRateId(activeRates[i].rate_id);
    }
  }, [activeRates]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (showBlock.value) {
      setDestination((0,_global_wc_cart__WEBPACK_IMPORTED_MODULE_5__.getDestination)(shippingRates));
    }
  }, [showBlock]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!destination || destination.country.trim() == "") {
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
  }, [destination]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!(0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.isOmnivaTerminalMethod)(selectedRateId)) {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('The selected delivery method is not delivery to the Omniva terminal');
      return;
    }
    if (!selectedRateId) {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Skipped retrieving dynamic Omniva data because the value of the selected rate ID is not received');
      return;
    }
    (0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.getDynamicOmnivaData)(mapValues.country, selectedRateId).then(response => {
      if (response.data) {
        const data = response.data;
        setOmnivaData(data);
      } else {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Failed to get dynamic Omniva data');
      }
    });
  }, [mapValues, selectedRateId]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if ((0,_global_utils__WEBPACK_IMPORTED_MODULE_9__.isObjectEmpty)(omnivaData)) {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Skipped getting Terminals because the Omniva dynamic data is empty');
      return;
    }
    if (omnivaData.terminals_type === false) {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('The selected delivery method does not have a terminals');
      return;
    }
    const terminalsType = 'terminals_type' in omnivaData ? omnivaData.terminals_type : 'omniva';
    (0,_global_terminals__WEBPACK_IMPORTED_MODULE_7__.getTerminalsByCountry)(mapValues.country, terminalsType).then(response => {
      if (response.data) {
        setTerminals(response.data);
      } else {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Failed to get terminals list');
      }
    });
  }, [omnivaData]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const text_obj = {
      title: _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.title_terminal,
      label: _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.select_terminal,
      error: _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.error_terminal
    };
    if (!(0,_global_utils__WEBPACK_IMPORTED_MODULE_9__.isObjectEmpty)(omnivaData) && 'terminals_type' in omnivaData) {
      if (omnivaData.terminals_type == 'post') {
        text_obj.title = _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.title_post;
        text_obj.label = _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.select_post;
        text_obj.error = _global_text__WEBPACK_IMPORTED_MODULE_8__.txt.error_post;
      }
    }
    setBlockText(text_obj);
  }, [omnivaData]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!terminals.length) {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Skipped updating terminals block because the terminals list is empty');
      return;
    }
    (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Updating terminal selection options...');
    const preparedTerminalsOptions = [{
      label: blockText.label,
      value: ''
    }];
    for (let i = 0; i < terminals.length; i++) {
      preparedTerminalsOptions.push({
        label: terminals[i].name,
        value: terminals[i].id
      });
    }
    setTerminalsOptions(preparedTerminalsOptions);
  }, [terminals]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (elemMapContainer.current) {
      if (elemMapContainer.current.innerHTML !== "") {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Removing previous custom field/map...');
        (0,_global_terminals__WEBPACK_IMPORTED_MODULE_7__.removeMap)(elemMapContainer.current);
      }
      let showMap = null;
      let autoselect = true;
      if ('show_map' in (0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.getOmnivaData)()) {
        showMap = (0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.getOmnivaData)().show_map;
        autoselect = (0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.getOmnivaData)().autoselect;
        setContainerParams({
          provider: 'provider' in omnivaData ? omnivaData.provider : 'unknown',
          type: 'terminals_type' in omnivaData ? omnivaData.terminals_type : 'unknown'
        });
      }
      if (showMap === true) {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Initializing TMJS map...');
        map.load_data({
          org_field: elemTerminalSelectField.current,
          map_container: elemMapContainer.current,
          terminals_type: omnivaData.terminals_type,
          provider: omnivaData.provider,
          country: mapValues.country,
          map_icon: omnivaData.map_icon,
          selected_terminal: selectedOmnivaTerminal
        });
        map.init(terminals);
        map.set_search_value(mapValues.postcode);
      } else if (showMap === false) {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Initializing terminal select field...');
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
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Failed to get map display param');
      }
    } else {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Failed to get container for custom field/map');
    }
  }, [terminalsOptions]);

  /* Handle changing the select's value */
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setExtensionData('omnivalt', 'selected_terminal', selectedOmnivaTerminal);
    if (selectedOmnivaTerminal !== '') {
      clearValidationError(terminalValidationErrorId);
      setContainerErrorClass('');
      return;
    }
    if (selectedOmnivaTerminal === '') {
      setValidationErrors({
        [terminalValidationErrorId]: {
          message: blockText.error,
          hidden: false
        }
      });
      setContainerErrorClass('error');
    }
  }, [setExtensionData, selectedOmnivaTerminal, setValidationErrors, clearValidationError, blockText]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setExtensionData('omnivalt', 'selected_rate_id', selectedRateId);
  }, [setExtensionData, selectedRateId]);
  if (!showBlock.value) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `omnivalt-terminal-select-container provider-${containerParams.provider} type-${containerParams.type} ${containerErrorClass}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "omnivalt-terminal-container-org",
    className: "omnivalt-org-select"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
    id: "omnivalt-terminal-select-field",
    label: blockText.title,
    value: selectedOmnivaTerminal,
    options: terminalsOptions,
    onChange: value => setSelectedOmnivaTerminal(value),
    ref: elemTerminalSelectField
  }), validationError?.hidden || selectedOmnivaTerminal !== '' ? null : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "wc-block-components-validation-error omnivalt-terminal-error"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, validationError?.message))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "omnivalt-terminal-container-map",
    className: "omnivalt-map-select",
    ref: elemMapContainer
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    class: "omnivalt-loader"
  })));
};

/***/ }),

/***/ "./src/terminal-selection-block/global/debug.js":
/*!******************************************************!*\
  !*** ./src/terminal-selection-block/global/debug.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   debug: () => (/* binding */ debug),
/* harmony export */   enableStateDebug: () => (/* binding */ enableStateDebug)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _omniva__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./omniva */ "./src/terminal-selection-block/global/omniva.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Variables
 */
const debug_enabled = (0,_omniva__WEBPACK_IMPORTED_MODULE_1__.getOmnivaData)().debug;
const prefix_debug = 'OMNIVA BLOCKS DEBUG:';

/**
 * Export functions
 */
const debug = (...variables) => {
  if (!debug_enabled) {
    return;
  }
  console.log(prefix_debug, ...variables);
};
const enableStateDebug = (stateName, stateValue) => {
  if (!debug_enabled) {
    return;
  }
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    console.log(prefix_debug, 'Changed state "' + stateName + '" value to:', stateValue);
  }, [stateValue]);
};

/***/ }),

/***/ "./src/terminal-selection-block/global/omniva.js":
/*!*******************************************************!*\
  !*** ./src/terminal-selection-block/global/omniva.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getDynamicOmnivaData: () => (/* binding */ getDynamicOmnivaData),
/* harmony export */   getOmnivaData: () => (/* binding */ getOmnivaData),
/* harmony export */   isOmnivaTerminalMethod: () => (/* binding */ isOmnivaTerminalMethod)
/* harmony export */ });
/* harmony import */ var _debug__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./debug */ "./src/terminal-selection-block/global/debug.js");
/**
 * Internal dependencies
 */


/**
 * Export functions
 */
const getOmnivaData = () => {
  if (!wcSettings || !wcSettings["omnivalt-blocks_data"]) {
    return [];
  }
  return wcSettings["omnivalt-blocks_data"];
};
const getDynamicOmnivaData = (country, method) => {
  return fetch(`${getOmnivaData().ajax_url}?action=omnivalt_get_dynamic_data&country=${country}&method=${method}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json'
    }
  }).then(response => response.json()).catch(error => {
    console.error('Error fetching terminals:', error);
    return [];
  });
};
const isOmnivaTerminalMethod = methodKey => {
  for (let [key, value] of Object.entries(getOmnivaData().methods)) {
    if (methodKey == value) {
      (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Detected Omniva method', value);
      return true;
    }
  }
  return false;
};

/***/ }),

/***/ "./src/terminal-selection-block/global/terminals.js":
/*!**********************************************************!*\
  !*** ./src/terminal-selection-block/global/terminals.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getTerminalsByCountry: () => (/* binding */ getTerminalsByCountry),
/* harmony export */   loadCustomSelect: () => (/* binding */ loadCustomSelect),
/* harmony export */   loadMap: () => (/* binding */ loadMap),
/* harmony export */   removeMap: () => (/* binding */ removeMap)
/* harmony export */ });
/* harmony import */ var _text__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./text */ "./src/terminal-selection-block/global/text.js");
/* harmony import */ var _omniva__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./omniva */ "./src/terminal-selection-block/global/omniva.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils */ "./src/terminal-selection-block/global/utils.js");



const markSelectControlValue = (selectElem, value) => {
  selectElem.value = value;
  const event = new Event('change', {
    bubbles: true
  });
  selectElem.dispatchEvent(event);
};
const getTerminalsByCountry = (country, type) => {
  return fetch(`${(0,_omniva__WEBPACK_IMPORTED_MODULE_1__.getOmnivaData)().ajax_url}?action=omnivalt_get_terminals&country=${country}&type=${type}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json'
    }
  }).then(response => response.json()).catch(error => {
    console.error('Error fetching terminals:', error);
    return [];
  });
};
const loadMap = () => {
  return {
    lib: null,
    elements: {},
    translations: {},
    params: {},
    load_data: function (params) {
      this.elements = {
        org_field: this.set_param(params, 'org_field', null),
        map_container: this.set_param(params, 'map_container', null)
      };
      this.params = {
        provider: this.set_param(params, 'provider', 'omniva'),
        terminals_type: this.set_param(params, 'terminals_type', 'terminal'),
        selected_terminal: this.set_param(params, 'selected_terminal', ''),
        icons_url: this.set_param(params, 'icons_url', `${(0,_omniva__WEBPACK_IMPORTED_MODULE_1__.getOmnivaData)().plugin_url}assets/img/terminal-mapping/`),
        country: this.set_param(params, 'country', 'LT'),
        //show_map: getOmnivaData().show_map,
        map_icon: this.set_param(params, 'map_icon', 'omnivalt_icon.png')
      };
      const modal_header = this.params.terminals_type == 'post' ? _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.modal_title_post : _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.modal_title_terminal;
      const provider = this.params.provider in _text__WEBPACK_IMPORTED_MODULE_0__.txt.providers ? _text__WEBPACK_IMPORTED_MODULE_0__.txt.providers[this.params.provider] : _text__WEBPACK_IMPORTED_MODULE_0__.txt.providers.omniva;
      this.translations = {
        modal_header: provider + " " + modal_header,
        terminal_list_header: this.params.terminals_type == 'post' ? _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.modal_search_title_post : _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.modal_search_title_terminal,
        select_pickup_point: this.params.terminals_type == 'post' ? _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.select_post : _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.select_terminal,
        seach_header: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.search_placeholder,
        search_btn: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.search_button,
        select_btn: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.select_button,
        modal_open_btn: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.modal_open_button,
        geolocation_btn: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.use_my_location,
        your_position: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.my_position,
        nothing_found: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.not_found,
        no_cities_found: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.no_cities_found,
        geolocation_not_supported: _text__WEBPACK_IMPORTED_MODULE_0__.txt.map.geo_not_supported
      };
    },
    set_param: function (all_params, param_key, fail_value = null) {
      if (!(param_key in all_params)) {
        return fail_value;
      }
      return all_params[param_key];
    },
    init: function (terminals) {
      if (!this.elements.map_container) {
        this.error('Failed to get a container for the map');
        return;
      }
      this.lib = new TerminalMappingOmnivalt();
      this.lib.setImagesPath(this.params.icons_url);
      this.lib.setTranslation(this.translations);
      this.lib.dom.setContainerParent(this.elements.map_container);
      this.lib.setParseMapTooltip((location, leafletCoords) => {
        let tip = location.address + " [" + location.id + "]";
        if (location.comment) {
          tip += "<br/><i>" + location.comment + "</i>";
        }
        return tip;
      });
      this.build_actions(this);
      this.lib.init({
        country_code: this.params.country,
        identifier: 'omnivalt',
        isModal: true,
        modalParent: this.elements.map_container,
        hideContainer: true,
        hideSelectBtn: true,
        cssThemeRule: 'tmjs-default-theme',
        customTileServerUrl: 'https://maps.omnivasiunta.lt/tile/{z}/{x}/{y}.png',
        customTileAttribution: '&copy; <a href="https://www.omniva.lt">Omniva</a>' + ' | Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
        terminalList: terminals
      });
    },
    build_actions: function (thisMap) {
      this.lib.sub('tmjs-ready', function (data) {
        thisMap.lib.map.createIcon('omnivalt_icon', thisMap.params.icons_url + thisMap.params.map_icon);
        thisMap.lib.map.refreshMarkerIcons();
        let selected_location = data.map.getLocationById(thisMap.params.selected_terminal);
        if (typeof selected_location != 'undefined' && selected_location != null) {
          thisMap.lib.dom.setActiveTerminal(selected_location);
          thisMap.lib.publish('terminal-selected', selected_location);
        }
      });
      this.lib.sub("terminal-selected", function (data) {
        markSelectControlValue(thisMap.elements.org_field, data.id);
        thisMap.lib.dom.setActiveTerminal(data.id);
        thisMap.lib.publish("close-map-modal");
      });
    },
    set_search_value: function (value) {
      value = value.trim();
      if (value == '') {
        return;
      }
      this.lib.dom.searchNearest(value);
      this.lib.dom.UI.modal.querySelector('.tmjs-search-input').value = value;
    },
    error: function (error_text) {
      console.error('OMNIVA MAP:', error_text);
    }
  };
};
const removeMap = mapContainer => {
  while (mapContainer.firstChild) {
    mapContainer.removeChild(mapContainer.lastChild);
  }
};
const loadCustomSelect = () => {
  return {
    map: null,
    selected: {},
    terminals: [],
    elements: {},
    params: {},
    translations: {},
    loaded: false,
    load_data: function (params) {
      this.elements = {
        org_field: this.set_param(params, 'org_field', null),
        custom_container: this.set_param(params, 'custom_container', null),
        this_dropdown: null,
        this_list: null,
        this_container: null,
        this_inner_container: null,
        this_loader: null,
        this_search: null,
        this_search_msg: null,
        this_show_more: null
      };
      this.params = {
        provider: this.set_param(params, 'provider', 'omniva'),
        terminals_type: this.set_param(params, 'terminals_type', 'terminal'),
        country: this.set_param(params, 'country', 'LT'),
        max_show: this.set_param(params, 'max_show', 8),
        active_timeout: null,
        autoselect: this.set_param(params, 'autoselect', true),
        selected_terminal: this.set_param(params, 'selected_terminal', '')
      };
      const provider = this.params.provider in _text__WEBPACK_IMPORTED_MODULE_0__.txt.providers ? _text__WEBPACK_IMPORTED_MODULE_0__.txt.providers[this.params.provider] : _text__WEBPACK_IMPORTED_MODULE_0__.txt.providers.omniva;
      this.translations = {
        not_found: _text__WEBPACK_IMPORTED_MODULE_0__.txt.select.not_found,
        too_short: _text__WEBPACK_IMPORTED_MODULE_0__.txt.select.search_too_short,
        select_terminal: this.params.terminals_type == 'post' ? _text__WEBPACK_IMPORTED_MODULE_0__.txt.select.post_select : _text__WEBPACK_IMPORTED_MODULE_0__.txt.select.terminal_select,
        enter_address: _text__WEBPACK_IMPORTED_MODULE_0__.txt.select.enter_address,
        show_more: _text__WEBPACK_IMPORTED_MODULE_0__.txt.select.show_more
      };
    },
    set_param: function (all_params, param_key, fail_value = null) {
      if (!(param_key in all_params)) {
        return fail_value;
      }
      return all_params[param_key];
    },
    init: function () {
      if ((0,_utils__WEBPACK_IMPORTED_MODULE_2__.isObjectEmpty)(this.elements)) {
        this.error('Load data is required before initialization');
        return;
      }
      if (!this.elements.custom_container) {
        this.error('Failed to get a container for the custom field');
        return;
      }
      if (this.elements.org_field.value) {
        this.set_selected();
      }
      let listElem,
        link,
        linkText = null;
      this.elements.this_container = document.createElement('div');
      this.elements.this_container.classList.add('omnivalt-terminals-list');
      this.elements.this_dropdown = document.createElement('div');
      this.elements.this_dropdown.classList.add('dropdown');
      this.elements.this_dropdown.innerHTML = this.translations.select_terminal;
      this.update_element_dropdown();
      this.elements.this_search = document.createElement('input');
      this.elements.this_search.type = 'text';
      this.elements.this_search.classList.add('search-input');
      this.elements.this_search.placeholder = this.translations.enter_address;
      this.elements.this_search_msg = document.createElement('span');
      this.elements.this_search_msg.classList.add('search-msg');
      this.show_element_search_msg(false);
      this.elements.this_loader = document.createElement('div');
      this.elements.this_loader.classList.add('omnivalt-loader');
      this.show_element_loader(false);
      this.elements.this_list = document.createElement('ul');
      this.elements.this_show_more = document.createElement('div');
      this.elements.this_show_more.classList.add('show-more');
      link = document.createElement('a');
      linkText = document.createTextNode(this.translations.show_more);
      link.appendChild(linkText);
      link.href = '#';
      this.elements.this_show_more.appendChild(link);
      this.elements.this_inner_container = document.createElement('div');
      this.elements.this_inner_container.classList.add('inner-container');
      this.show_element_inner_container(false);
      this.elements.this_inner_container.append(this.elements.this_search, this.elements.this_search_msg, this.elements.this_loader, this.elements.this_list, this.elements.this_show_more);
      this.elements.this_container.append(this.elements.this_dropdown, this.elements.this_inner_container);
      this.elements.custom_container.append(this.elements.this_container);
      this.reset_terminals();
      this.refresh_element_list();

      /* Events */
      this.elements.this_show_more.addEventListener('click', e => {
        e.preventDefault();
        this.show_element_all_options();
      });
      this.elements.this_dropdown.addEventListener('click', e => {
        this.toggle_dropdown();
      });
      this.elements.org_field.addEventListener('click', e => {
        this.set_selected();
        this.update_element_dropdown();
        this.elements.this_list.querySelector('li[data-id="' + this.elements.org_field.value + '"').classList.add('selected');
      });
      this.elements.this_search.addEventListener('keyup', () => {
        this.show_element_search_msg(false);
        this.show_element_loader(true);
        clearTimeout(this.params.active_timeout);
        this.params.active_timeout = setTimeout(() => {
          this.params.autoselect = false;
          this.geo_suggest(this.elements.this_search.value);
        }, 400);
      });
      this.elements.this_search.addEventListener('keyup', e => {
        if (e.which == '13') {
          e.preventDefault();
        }
      });
      document.addEventListener('mousedown', e => {
        if (this.elements.this_container != e.target && !this.elements.this_container.contains(e.target) && this.elements.this_container.classList.contains('open')) {
          this.toggle_dropdown(true);
        }
      });
      this.loaded = true;
    },
    set_terminals: function (terminals) {
      for (let i = 0; i < terminals.length; i++) {
        terminals[i]['distance'] = false;
      }
      this.terminals = terminals;
    },
    set_selected: function () {
      this.selected = {
        id: this.params.selected_terminal,
        text: this.elements.org_field.options[this.elements.org_field.selectedIndex].text,
        distance: false
      };
    },
    set_search_value: function (value) {
      this.elements.this_search.value = value.trim();
      this.geo_suggest(this.elements.this_search.value);
    },
    activate_autoselect: function () {
      if (this.params.selected_terminal == '') {
        let firstElem = this.elements.this_list.querySelector('li:not(.city)');
        this.mark_element_list_selected(firstElem);
      }
    },
    update_element_dropdown: function () {
      if ('text' in this.selected) {
        this.elements.this_dropdown.innerHTML = this.selected.text;
      }
    },
    show_element_loader: function (show = false) {
      if (show) {
        this.elements.this_loader.style.display = 'block';
      } else {
        this.elements.this_loader.style.display = 'none';
      }
    },
    show_element_search_msg: function (show = false) {
      if (show) {
        this.elements.this_search_msg.style.display = 'block';
      } else {
        this.elements.this_search_msg.style.display = 'none';
      }
    },
    show_element_inner_container: function (show = false) {
      if (show) {
        this.elements.this_inner_container.style.display = 'block';
      } else {
        this.elements.this_inner_container.style.display = 'none';
      }
    },
    refresh_element_list: function () {
      let counter = 0;
      let city = false;
      let html = '';
      let listElem,
        listCityElem,
        boldTextElem,
        textElem,
        selectedElem = null;
      let topOffset = 0;
      this.clear_element_list();
      for (let terminal of this.terminals) {
        listElem = document.createElement('li');
        listElem.setAttribute('data-id', terminal.id);
        listElem.innerHTML = terminal.name;
        if ('distance' in terminal && terminal.distance !== false) {
          boldTextElem = document.createElement('strong');
          textElem = document.createTextNode('' + terminal.distance + 'km');
          boldTextElem.appendChild(textElem);
          listElem.innerHTML += ' ' + boldTextElem.outerHTML;
          counter++;
        } else {
          this.elements.this_show_more.style.display = 'none';
        }
        if ('id' in this.selected && this.selected.id == terminal.id) {
          listElem.classList.add('selected');
        }
        if (counter > this.params.max_show) {
          listElem.style.display = 'none';
        }
        if (city != terminal.city) {
          listCityElem = document.createElement('li');
          listCityElem.classList.add('city');
          listCityElem.innerHTML = terminal.city;
          if (counter > this.params.max_show) {
            listCityElem.style.display = 'none';
          }
          this.elements.this_list.append(listCityElem);
          city = terminal.city;
        }
        this.elements.this_list.append(listElem);
      }
      this.elements.this_list.querySelectorAll('li:not(.city)').forEach(el => el.addEventListener('click', () => {
        this.mark_element_list_selected(el);
      }));
    },
    clear_element_list: function () {
      while (this.elements.this_list.firstChild) {
        this.elements.this_list.removeChild(this.elements.this_list.lastChild);
      }
    },
    unmark_element_list_selected: function () {
      let selectedElem = this.elements.this_list.querySelector('li.selected');
      if (selectedElem) {
        selectedElem.classList.remove('selected');
      }
    },
    mark_element_list_selected: function (listElem) {
      this.unmark_element_list_selected();
      const selectedTerminal = listElem.getAttribute('data-id');
      listElem.classList.add('selected');
      markSelectControlValue(this.elements.org_field, selectedTerminal);
      this.params.selected_terminal = selectedTerminal;
      this.set_selected();
      this.update_element_dropdown();
      this.toggle_dropdown(true);
    },
    toggle_dropdown: function (forceClose = false) {
      if (this.elements.this_container.classList.contains('open') || forceClose) {
        this.show_element_inner_container(false);
        this.elements.this_container.classList.remove('open');
      } else {
        this.show_element_inner_container(true);
        this.elements.this_container.classList.add('open');
      }
    },
    show_element_all_options: function () {
      this.elements.this_list.querySelectorAll('li').forEach(el => {
        el.style.display = '';
        this.elements.this_show_more.style.display = 'none';
      });
    },
    hide_element_all_options: function () {
      this.elements.this_list.querySelectorAll('li').forEach(el => {
        el.style.display = 'none';
        this.elements.this_show_more.style.display = '';
      });
    },
    reset_terminals: function () {
      for (let i = 0; i < this.terminals.length; i++) {
        this.terminals[i].distance = false;
      }
      this.terminals.sort(function (a, b) {
        //Sort by name
        return a.city.localeCompare(b.city) || b.name - a.name;
      });
    },
    geo_find_position: async function (address) {
      if (address == '' || address.length < 3) {
        this.reset_terminals();
        this.show_element_all_options();
        this.refresh_element_list();
        return false;
      }
      let url = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?singleLine=' + address + '&sourceCountry=' + this.params.country + '&category=&outFields=Postal&maxLocations=1&forStorage=false&f=pjson';
      const fetchData = async () => {
        const data = await (0,_utils__WEBPACK_IMPORTED_MODULE_2__.getJsonDataFromUrl)(url);
        return data;
      };
      let location_result = await fetchData();
      if ('candidates' in location_result && location_result.candidates.length) {
        let location = location_result.candidates[0];
        this.sort_list_by_distance(location.location.y, location.location.x);
        this.refresh_element_list();
        this.elements.this_show_more.style.display = '';
        if (this.params.autoselect) {
          this.activate_autoselect();
        }
      }
    },
    geo_suggest: async function (address) {
      if (address == '' || address.length < 3) {
        this.reset_terminals();
        this.show_element_all_options();
        this.refresh_element_list();
        this.show_element_loader(false);
        if (address.length) {
          this.elements.this_search_msg.innerHTML = this.translations.too_short;
          this.show_element_search_msg(true);
        }
        return false;
      }
      let url = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/suggest?text=' + address + '&f=pjson&sourceCountry=' + this.params.country + '&maxSuggestions=1';
      const fetchData = async () => {
        const data = await (0,_utils__WEBPACK_IMPORTED_MODULE_2__.getJsonDataFromUrl)(url);
        return data;
      };
      let suggest_result = await fetchData();
      if ('suggestions' in suggest_result && suggest_result.suggestions.length) {
        this.geo_find_position(suggest_result.suggestions[0].text);
      } else {
        this.elements.this_search_msg.innerHTML = this.translations.not_found;
        this.show_element_search_msg(true);
        this.hide_element_all_options();
      }
      this.show_element_loader(false);
    },
    sort_list_by_distance: function (y, x) {
      let distance;
      for (let i = 0; i < this.terminals.length; i++) {
        distance = this.calculate_distance(y, x, this.terminals[i].coords.lat, this.terminals[i].coords.lng);
        this.terminals[i].distance = distance.toFixed(2);
      }
      this.terminals.sort((a, b) => {
        let dist1 = a.distance;
        let dist2 = b.distance;
        if (parseFloat(dist1) < parseFloat(dist2)) {
          return -1;
        }
        if (parseFloat(dist1) > parseFloat(dist2)) {
          return 1;
        }
        return 0;
      });
    },
    calculate_distance: function (lat1, lon1, lat2, lon2) {
      let R = 6371;
      let dLat = this.to_radius(lat2 - lat1);
      let dLon = this.to_radius(lon2 - lon1);
      lat1 = this.to_radius(lat1);
      lat2 = this.to_radius(lat2);
      let a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.sin(dLon / 2) * Math.sin(dLon / 2) * Math.cos(lat1) * Math.cos(lat2);
      let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      let d = R * c;
      return d;
    },
    to_radius(value) {
      return value * Math.PI / 180;
    },
    error: function (error_text) {
      console.error('OMNIVA CUSTOM SELECT:', error_text);
    }
  };
};

/***/ }),

/***/ "./src/terminal-selection-block/global/text.js":
/*!*****************************************************!*\
  !*** ./src/terminal-selection-block/global/text.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   txt: () => (/* binding */ txt)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

const txt = {
  block_options: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Block options', 'omnivalt'),
  title_terminal: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Parcel terminal', 'omnivalt'),
  select_terminal: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select parcel terminal', 'omnivalt'),
  error_terminal: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please select parcel terminal', 'omnivalt'),
  cart_terminal_info: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('You can choose the parcel terminal on the Checkout page', 'omnivalt'),
  loading_field: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading select field...', 'omnivalt'),
  title_post: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Post office', 'omnivalt'),
  select_post: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select post office', 'omnivalt'),
  error_post: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please select post office', 'omnivalt'),
  cart_post_info: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('You can choose the post office on the Checkout page', 'omnivalt'),
  providers: {
    omniva: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Omniva', 'omnivalt'),
    matkahuolto: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Matkahuolto', 'omnivalt')
  },
  map: {
    modal_title_post: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('post offices', 'omnivalt'),
    modal_title_terminal: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('parcel terminals', 'omnivalt'),
    modal_search_title_post: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Post offices list', 'omnivalt'),
    modal_search_title_terminal: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Parcel terminals list', 'omnivalt'),
    select_post: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select post office', 'omnivalt'),
    select_terminal: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select terminal', 'omnivalt'),
    search_placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Enter postcode', 'omnivalt'),
    search_button: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Search', 'omnivalt'),
    select_button: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select', 'omnivalt'),
    modal_open_button: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select in map', 'omnivalt'),
    use_my_location: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Use my location', 'omnivalt'),
    my_position: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Distance calculated from this point', 'omnivalt'),
    not_found: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Place not found', 'omnivalt'),
    no_cities_found: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('There were no cities found for your search term', 'omnivalt'),
    geo_not_supported: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Geolocation is not supported', 'omnivalt')
  },
  select: {
    not_found: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Place not found', 'omnivalt'),
    search_too_short: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Value is too short', 'omnivalt'),
    terminal_select: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select terminal', 'omnivalt'),
    terminal_map_title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('parcel terminals', 'omnivalt'),
    terminal_map_search_title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Parcel terminals addresses', 'omnivalt'),
    post_select: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select post office', 'omnivalt'),
    post_map_title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('post offices', 'omnivalt'),
    post_map_search_title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Post offices addresses', 'omnivalt'),
    enter_address: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Enter postcode/address', 'omnivalt'),
    show_in_map: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show in map', 'omnivalt'),
    show_more: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show more', 'omnivalt')
  }
};

/***/ }),

/***/ "./src/terminal-selection-block/global/utils.js":
/*!******************************************************!*\
  !*** ./src/terminal-selection-block/global/utils.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   addTokenToValue: () => (/* binding */ addTokenToValue),
/* harmony export */   buildToken: () => (/* binding */ buildToken),
/* harmony export */   getJsonDataFromUrl: () => (/* binding */ getJsonDataFromUrl),
/* harmony export */   getObjectValue: () => (/* binding */ getObjectValue),
/* harmony export */   insertAfter: () => (/* binding */ insertAfter),
/* harmony export */   isObjectEmpty: () => (/* binding */ isObjectEmpty)
/* harmony export */ });
const buildToken = length => {
  let result = '';
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  const charactersLength = characters.length;
  let counter = 0;
  while (counter < length) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
    counter += 1;
  }
  return result;
};
const addTokenToValue = (value, tokenLength = 0) => {
  if (!tokenLength) {
    tokenLength = 5;
  }
  return {
    value: value,
    token: buildToken(tokenLength)
  };
};
const isObjectEmpty = obj => {
  for (const prop in obj) {
    if (Object.hasOwn(obj, prop)) {
      return false;
    }
  }
  return true;
};
const getObjectValue = (obj, key, valueIsNot = null) => {
  if (isObjectEmpty(obj) || !(key in obj)) {
    return valueIsNot;
  }
  return obj[key];
};
const insertAfter = (elem, newElem, afterElem = null) => {
  afterElem = afterElem ? afterElem.nextSibling : elem.firstChild;
  elem.insertBefore(newElem, afterElem);
};
const getJsonDataFromUrl = async url => {
  let responseData = null;
  try {
    let response = await fetch(url);
    responseData = await response.json();
  } catch (error) {
    console.error('OMNIVA UTILS:', error);
  }
  return responseData;
};

/***/ }),

/***/ "./src/terminal-selection-block/global/wc-cart.js":
/*!********************************************************!*\
  !*** ./src/terminal-selection-block/global/wc-cart.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getActiveShippingRates: () => (/* binding */ getActiveShippingRates),
/* harmony export */   getDestination: () => (/* binding */ getDestination),
/* harmony export */   getShippingCountry: () => (/* binding */ getShippingCountry)
/* harmony export */ });
/* harmony import */ var _debug__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./debug */ "./src/terminal-selection-block/global/debug.js");
/**
 * Internal dependencies
 */


/**
 * Export functions
 */
const getShippingCountry = shippingAddress => {
  (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Getting shipping country...');
  if (shippingAddress.country.trim() == "") {
    (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Shipping country LT');
    return 'LT';
  }
  (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Shipping country', shippingAddress.country);
  return shippingAddress.country;
};
const getDestination = (shippingRates, getFirst = true) => {
  (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Getting destination...');
  if (!shippingRates.length) {
    (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Failed to get destination because shipping rates is empty');
    return null;
  }
  let allDestinations = [];
  for (let i = 0; i < shippingRates.length; i++) {
    if (!shippingRates[i].destination) {
      continue;
    }
    allDestinations.push(shippingRates[i].destination);
  }
  if (!allDestinations.length) {
    (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Failed to get destination');
    return null;
  }
  if (!getFirst) {
    (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Destinations', allDestinations);
    return allDestinations;
  }
  (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('First destination', allDestinations[0]);
  return allDestinations[0];
};
const getActiveShippingRates = shippingRates => {
  if (!shippingRates.length) {
    return [];
  }
  let activeRates = [];
  for (let i = 0; i < shippingRates.length; i++) {
    if (!shippingRates[i].shipping_rates) {
      continue;
    }
    for (let j = 0; j < shippingRates[i].shipping_rates.length; j++) {
      activeRates.push(shippingRates[i].shipping_rates[j]);
    }
  }
  return activeRates;
};

/***/ }),

/***/ "./src/terminal-selection-block/checkout/frontend.scss":
/*!*************************************************************!*\
  !*** ./src/terminal-selection-block/checkout/frontend.scss ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "lodash":
/*!*************************!*\
  !*** external "lodash" ***!
  \*************************/
/***/ ((module) => {

module.exports = window["lodash"];

/***/ }),

/***/ "@woocommerce/blocks-checkout":
/*!****************************************!*\
  !*** external ["wc","blocksCheckout"] ***!
  \****************************************/
/***/ ((module) => {

module.exports = window["wc"]["blocksCheckout"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./src/terminal-selection-block/checkout/block.json":
/*!**********************************************************!*\
  !*** ./src/terminal-selection-block/checkout/block.json ***!
  \**********************************************************/
/***/ ((module) => {

module.exports = JSON.parse('{"apiVersion":2,"name":"omnivalt/terminal-selection-checkout","version":"0.0.2","title":"Omniva terminal selection","category":"woocommerce","description":"Allow to add components for Omniva shipping method","parent":["woocommerce/checkout-shipping-methods-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false},"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","default":""}},"textdomain":"omnivalt"}');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!***********************************************************!*\
  !*** ./src/terminal-selection-block/checkout/frontend.js ***!
  \***********************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _block__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./block */ "./src/terminal-selection-block/checkout/block.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block.json */ "./src/terminal-selection-block/checkout/block.json");
/* harmony import */ var _frontend_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./frontend.scss */ "./src/terminal-selection-block/checkout/frontend.scss");




(0,_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__.registerCheckoutBlock)({
  metadata: _block_json__WEBPACK_IMPORTED_MODULE_2__,
  component: _block__WEBPACK_IMPORTED_MODULE_1__.Block
});
})();

/******/ })()
;
//# sourceMappingURL=frontend.js.map