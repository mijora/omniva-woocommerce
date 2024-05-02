/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/terminal-selection-block/cart/block.js":
/*!****************************************************!*\
  !*** ./src/terminal-selection-block/cart/block.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Block: () => (/* binding */ Block)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _global_wc_cart__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../global/wc-cart */ "./src/terminal-selection-block/global/wc-cart.js");
/* harmony import */ var _global_omniva__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../global/omniva */ "./src/terminal-selection-block/global/omniva.js");
/* harmony import */ var _global_text__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../global/text */ "./src/terminal-selection-block/global/text.js");
/* harmony import */ var _global_utils__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../global/utils */ "./src/terminal-selection-block/global/utils.js");
/* harmony import */ var _global_debug__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../global/debug */ "./src/terminal-selection-block/global/debug.js");








const Block = ({
  className
}) => {
  const [selectedRateId, setSelectedRateId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [showBlock, setShowBlock] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)((0,_global_utils__WEBPACK_IMPORTED_MODULE_6__.addTokenToValue)(false)); //Need token to avoid undetected change when changing true>false>true in other useEffect functions

  (0,_global_debug__WEBPACK_IMPORTED_MODULE_7__.enableStateDebug)('Block show', showBlock);
  (0,_global_debug__WEBPACK_IMPORTED_MODULE_7__.enableStateDebug)('Selected rate ID', selectedRateId);
  const shippingRates = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useSelect)(select => {
    const store = select('wc/store/cart');
    return store.getCartData().shippingRates;
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setShowBlock((0,_global_utils__WEBPACK_IMPORTED_MODULE_6__.addTokenToValue)(false));
    if (shippingRates.length) {
      const activeRates = (0,_global_wc_cart__WEBPACK_IMPORTED_MODULE_3__.getActiveShippingRates)(shippingRates);
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_7__.debug)('Received ' + activeRates.length + ' active rates:', activeRates);
      for (let i = 0; i < activeRates.length; i++) {
        if (!activeRates[i].rate_id) {
          continue;
        }
        if ((0,_global_omniva__WEBPACK_IMPORTED_MODULE_4__.isOmnivaTerminalMethod)(activeRates[i].rate_id) && activeRates[i].selected) {
          setShowBlock((0,_global_utils__WEBPACK_IMPORTED_MODULE_6__.addTokenToValue)(true));
        }
        if (activeRates[i].selected) {
          setSelectedRateId(activeRates[i].rate_id);
        }
      }
    } else {
      (0,_global_debug__WEBPACK_IMPORTED_MODULE_7__.debug)('Empty param: shippingRates');
    }
  }, [shippingRates]);
  if (!showBlock.value) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'wc-block-components-totals-wrapper'
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: 'wc-block-components-totals-item'
  }, _global_text__WEBPACK_IMPORTED_MODULE_5__.txt.cart_terminal_info));
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

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@woocommerce/blocks-checkout":
/*!****************************************!*\
  !*** external ["wc","blocksCheckout"] ***!
  \****************************************/
/***/ ((module) => {

module.exports = window["wc"]["blocksCheckout"];

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

/***/ "./src/terminal-selection-block/cart/block.json":
/*!******************************************************!*\
  !*** ./src/terminal-selection-block/cart/block.json ***!
  \******************************************************/
/***/ ((module) => {

module.exports = JSON.parse('{"apiVersion":2,"name":"omnivalt/terminal-selection-cart","version":"0.0.3","title":"Omniva terminal information","category":"woocommerce","description":"Allow to add components for Omniva shipping method","parent":["woocommerce/cart-order-summary-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false},"attributes":{"lock":{"type":"object","default":{"remove":false,"move":false}}},"textdomain":"omnivalt"}');

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
/*!*******************************************************!*\
  !*** ./src/terminal-selection-block/cart/frontend.js ***!
  \*******************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _block__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./block */ "./src/terminal-selection-block/cart/block.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block.json */ "./src/terminal-selection-block/cart/block.json");



(0,_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__.registerCheckoutBlock)({
  metadata: _block_json__WEBPACK_IMPORTED_MODULE_2__,
  component: _block__WEBPACK_IMPORTED_MODULE_1__.Block
});
})();

/******/ })()
;
//# sourceMappingURL=frontend.js.map