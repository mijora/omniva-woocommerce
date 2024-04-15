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
  const [country, setCountry] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
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
  const elemTerminalSelectField = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const elemMapContainer = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const map = (0,_global_terminals__WEBPACK_IMPORTED_MODULE_7__.loadMap)();
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Block show', showBlock);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Terminals list', terminals);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.enableStateDebug)('Selected country', country);
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
      setCountry((0,_global_wc_cart__WEBPACK_IMPORTED_MODULE_5__.getDestinationCountry)(shippingRates));
    }
  }, [showBlock]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!(0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.isOmnivaTerminalMethod)(selectedRateId)) {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('The selected delivery method is not delivery to the Omniva terminal');
      return;
    }
    if (!selectedRateId) {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Skipped retrieving dynamic Omniva data because the value of the selected rate ID is not received');
      return;
    }
    if (country == '') {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Skipped retrieving dynamic Omniva data because the value of the country is empty');
      return;
    }
    (0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.getDynamicOmnivaData)(country, selectedRateId).then(response => {
      if (response.data) {
        const data = response.data;
        data.country = country;
        setOmnivaData(data);
      } else {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Failed to get dynamic Omniva data');
      }
    });
  }, [country, selectedRateId]);
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
    (0,_global_terminals__WEBPACK_IMPORTED_MODULE_7__.getTerminalsByCountry)(country, terminalsType).then(response => {
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
    if (elemMapContainer.current) {
      if (elemMapContainer.current.innerHTML !== "") {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Removing previous map...');
        (0,_global_terminals__WEBPACK_IMPORTED_MODULE_7__.removeMap)(elemMapContainer.current);
      }
      let showMap = null;
      if ('show_map' in (0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.getOmnivaData)()) {
        showMap = (0,_global_omniva__WEBPACK_IMPORTED_MODULE_6__.getOmnivaData)().show_map;
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
          country: country,
          map_icon: omnivaData.map_icon,
          selected_terminal: selectedOmnivaTerminal
        });
        map.init(terminals);
      } else if (showMap === false) {
        console.log('Nerodo zemes'); //TODO: Padaryti custom select field
      } else {
        (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Failed to get map display param');
      }
    } else {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_10__.debug)('Failed to get map container');
    }
  }, [terminals]);

  /* Handle changing the select's value */
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setExtensionData('omnivalt', 'selected_terminal', selectedOmnivaTerminal);
    if (selectedOmnivaTerminal !== '') {
      clearValidationError(terminalValidationErrorId);
      return;
    }
    if (selectedOmnivaTerminal === '') {
      setValidationErrors({
        [terminalValidationErrorId]: {
          message: blockText.error,
          hidden: false
        }
      });
    }
  }, [setExtensionData, selectedOmnivaTerminal, setValidationErrors, clearValidationError, blockText]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setExtensionData('omnivalt', 'selected_rate_id', selectedRateId);
  }, [setExtensionData, selectedRateId]);
  if (!showBlock.value) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: `omnivalt-terminal-select-container provider-${containerParams.provider} type-${containerParams.type}`
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
  }));
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
/* harmony export */   loadMap: () => (/* binding */ loadMap),
/* harmony export */   removeMap: () => (/* binding */ removeMap)
/* harmony export */ });
/* harmony import */ var _text__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./text */ "./src/terminal-selection-block/global/text.js");
/* harmony import */ var _omniva__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./omniva */ "./src/terminal-selection-block/global/omniva.js");


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
        //TODO: Prideti provider gavima
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
        console.error('OMNIVA MAP: Failed to get a container for the map');
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
        thisMap.elements.org_field.value = data.id;
        const event = new Event('change', {
          bubbles: true
        });
        thisMap.elements.org_field.dispatchEvent(event);
        thisMap.lib.dom.setActiveTerminal(data.id);
        thisMap.lib.publish("close-map-modal");
      });
    }
  };
};
const removeMap = mapContainer => {
  while (mapContainer.firstChild) {
    mapContainer.removeChild(mapContainer.lastChild);
  }
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
/* harmony export */   getObjectValue: () => (/* binding */ getObjectValue),
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

/***/ }),

/***/ "./src/terminal-selection-block/global/wc-cart.js":
/*!********************************************************!*\
  !*** ./src/terminal-selection-block/global/wc-cart.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getActiveShippingRates: () => (/* binding */ getActiveShippingRates),
/* harmony export */   getDestinationCountry: () => (/* binding */ getDestinationCountry),
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
const getDestinationCountry = shippingRates => {
  (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Getting destination country...');
  if (!shippingRates.length) {
    (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Destination country LT');
    return 'LT';
  }
  let country = '';
  for (let i = 0; i < shippingRates.length; i++) {
    if (!shippingRates[i].destination.country || shippingRates[i].destination.country.trim() == "") {
      continue;
    }
    country = shippingRates[i].destination.country.trim();
  }
  (0,_debug__WEBPACK_IMPORTED_MODULE_0__.debug)('Destination country', country);
  return country;
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