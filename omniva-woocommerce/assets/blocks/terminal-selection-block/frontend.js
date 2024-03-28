/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/terminal-selection-block/block.js":
/*!***********************************************!*\
  !*** ./src/terminal-selection-block/block.js ***!
  \***********************************************/
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
/* harmony import */ var _options__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./options */ "./src/terminal-selection-block/options.js");
/* harmony import */ var _wc_cart__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./wc-cart */ "./src/terminal-selection-block/wc-cart.js");
/* harmony import */ var _omniva__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./omniva */ "./src/terminal-selection-block/omniva.js");
/* harmony import */ var _terminals__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./terminals */ "./src/terminal-selection-block/terminals.js");
/* harmony import */ var _text__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./text */ "./src/terminal-selection-block/text.js");

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
  const [terminals, setTerminals] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([{
    label: _text__WEBPACK_IMPORTED_MODULE_9__.txt.select_terminal,
    value: ''
  }]);
  const [showBlock, setShowBlock] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [selectedOmnivaTerminal, setSelectedOmnivaTerminal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selectedRateId, setSelectedRateId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
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
    setShowBlock(false);
    if (shippingRates.length) {
      const activeRates = (0,_wc_cart__WEBPACK_IMPORTED_MODULE_6__.getActiveShippingRates)(shippingRates);
      for (let i = 0; i < activeRates.length; i++) {
        if (!activeRates[i].rate_id) {
          continue;
        }
        if ((0,_omniva__WEBPACK_IMPORTED_MODULE_7__.isOmnivaTerminalMethod)(activeRates[i].rate_id) && activeRates[i].selected) {
          setShowBlock(true);
        }
        if (activeRates[i].selected) {
          setSelectedRateId(activeRates[i].rate_id);
        }
      }
    }
  }, [shippingRates]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (showBlock) {
      (0,_terminals__WEBPACK_IMPORTED_MODULE_8__.getTerminalsByCountry)((0,_wc_cart__WEBPACK_IMPORTED_MODULE_6__.getDestinationCountry)(shippingRates)).then(terminals => {
        if (terminals.data) {
          setTerminals(terminals.data);
        }
      });
    }
  }, [showBlock]);

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
          message: _text__WEBPACK_IMPORTED_MODULE_9__.txt.error_terminal,
          hidden: false
        }
      });
    }
  }, [setExtensionData, selectedOmnivaTerminal, setValidationErrors, clearValidationError]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setExtensionData('omnivalt', 'selected_rate_id', selectedRateId);
  }, [setExtensionData, selectedRateId]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    //TODO: laikinai
    console.log('Terminalas', selectedOmnivaTerminal);
  }, [selectedOmnivaTerminal]);
  if (!showBlock) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "omnivalt_terminal_select_container"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
    label: _text__WEBPACK_IMPORTED_MODULE_9__.txt.title_terminal,
    value: selectedOmnivaTerminal,
    options: terminals,
    onChange: setSelectedOmnivaTerminal
  }), validationError?.hidden || selectedOmnivaTerminal !== '' ? null : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "wc-block-components-validation-error omnivalt-terminal-error"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, validationError?.message)));
};

/***/ }),

/***/ "./src/terminal-selection-block/omniva.js":
/*!************************************************!*\
  !*** ./src/terminal-selection-block/omniva.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getOmnivaData: () => (/* binding */ getOmnivaData),
/* harmony export */   isOmnivaTerminalMethod: () => (/* binding */ isOmnivaTerminalMethod)
/* harmony export */ });
/**
 * Export functions
 */
const getOmnivaData = () => {
  if (!wcSettings || !wcSettings["omnivalt-blocks_data"]) {
    return [];
  }
  return wcSettings["omnivalt-blocks_data"];
};
const isOmnivaTerminalMethod = methodKey => {
  for (let [key, value] of Object.entries(getOmnivaData().methods)) {
    if (methodKey == value) {
      return true;
    }
  }
  return false;
};

/***/ }),

/***/ "./src/terminal-selection-block/options.js":
/*!*************************************************!*\
  !*** ./src/terminal-selection-block/options.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   options: () => (/* binding */ options)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _text__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./text */ "./src/terminal-selection-block/text.js");
/**
 * External dependencies
 */


const options = [{
  label: _text__WEBPACK_IMPORTED_MODULE_1__.txt.select_terminal,
  value: ''
}, {
  label: "Testas",
  value: 'test'
}];

/***/ }),

/***/ "./src/terminal-selection-block/terminals.js":
/*!***************************************************!*\
  !*** ./src/terminal-selection-block/terminals.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getTerminalsByCountry: () => (/* binding */ getTerminalsByCountry)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _text__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./text */ "./src/terminal-selection-block/text.js");
/**
 * External dependencies
 */


const {
  omnivalt
} = wcSettings.checkoutData.extensions;
const getTerminalsByCountry = country => {
  return fetch(`${omnivalt.ajax_url}?action=omnivalt_get_terminals&country=${country}`, {
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

/***/ }),

/***/ "./src/terminal-selection-block/text.js":
/*!**********************************************!*\
  !*** ./src/terminal-selection-block/text.js ***!
  \**********************************************/
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
  error_terminal: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Please select parcel terminal', 'omnivalt')
};

/***/ }),

/***/ "./src/terminal-selection-block/wc-cart.js":
/*!*************************************************!*\
  !*** ./src/terminal-selection-block/wc-cart.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getActiveShippingRates: () => (/* binding */ getActiveShippingRates),
/* harmony export */   getDestinationCountry: () => (/* binding */ getDestinationCountry),
/* harmony export */   getShippingCountry: () => (/* binding */ getShippingCountry)
/* harmony export */ });
/**
 * Export functions
 */
const getShippingCountry = shippingAddress => {
  if (shippingAddress.country.trim() == "") {
    return 'LT';
  }
  return shippingAddress.country;
};
const getDestinationCountry = shippingRates => {
  if (!shippingRates.length) {
    return 'LT';
  }
  let country = '';
  for (let i = 0; i < shippingRates.length; i++) {
    if (!shippingRates[i].destination.country || shippingRates[i].destination.country.trim() == "") {
      continue;
    }
    country = shippingRates[i].destination.country.trim();
  }
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

/***/ "./src/terminal-selection-block/block.json":
/*!*************************************************!*\
  !*** ./src/terminal-selection-block/block.json ***!
  \*************************************************/
/***/ ((module) => {

module.exports = JSON.parse('{"apiVersion":2,"name":"omnivalt/terminal-selection","version":"0.0.2","title":"Omniva terminal selection","category":"woocommerce","description":"Allow to add components for Omniva shipping method","parent":["woocommerce/checkout-shipping-methods-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false},"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","default":""}},"textdomain":"omnivalt"}');

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
/*!**************************************************!*\
  !*** ./src/terminal-selection-block/frontend.js ***!
  \**************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _block__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./block */ "./src/terminal-selection-block/block.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block.json */ "./src/terminal-selection-block/block.json");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


(0,_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__.registerCheckoutBlock)({
  metadata: _block_json__WEBPACK_IMPORTED_MODULE_2__,
  component: _block__WEBPACK_IMPORTED_MODULE_1__.Block
});
})();

/******/ })()
;
//# sourceMappingURL=frontend.js.map