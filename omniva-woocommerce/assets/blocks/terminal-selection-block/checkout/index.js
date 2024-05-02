/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/terminal-selection-block/checkout/edit.js":
/*!*******************************************************!*\
  !*** ./src/terminal-selection-block/checkout/edit.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Edit: () => (/* binding */ Edit),
/* harmony export */   Save: () => (/* binding */ Save)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _options__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./options */ "./src/terminal-selection-block/checkout/options.js");
/* harmony import */ var _global_text__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../global/text */ "./src/terminal-selection-block/global/text.js");

/**
 * External dependencies
 */





/**
 * Internal dependencies
 */
//import './style.scss';


const Edit = ({
  attributes,
  setAttributes
}) => {
  const {
    text
  } = attributes;
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.useBlockProps)();
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...blockProps,
    style: {
      display: 'block'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: _global_text__WEBPACK_IMPORTED_MODULE_6__.txt.block_options
  }, "Options for the block go here.")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.RichText, {
    value: text || _global_text__WEBPACK_IMPORTED_MODULE_6__.txt.title_terminal,
    onChange: value => setAttributes({
      text: value
    })
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Disabled, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
    options: _options__WEBPACK_IMPORTED_MODULE_5__.options
  }))));
};
const Save = ({
  attributes
}) => {
  const {
    text
  } = attributes;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ..._wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.useBlockProps.save()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.RichText.Content, {
    value: text
  }));
};

/***/ }),

/***/ "./src/terminal-selection-block/checkout/options.js":
/*!**********************************************************!*\
  !*** ./src/terminal-selection-block/checkout/options.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   options: () => (/* binding */ options)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _global_text__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../global/text */ "./src/terminal-selection-block/global/text.js");
/**
 * External dependencies
 */


const options = [{
  label: _global_text__WEBPACK_IMPORTED_MODULE_1__.txt.select_terminal,
  value: ''
}, {
  label: "Testas",
  value: 'test'
}];

/***/ }),

/***/ "./src/terminal-selection-block/global/icons.js":
/*!******************************************************!*\
  !*** ./src/terminal-selection-block/global/icons.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   omnivaVertical: () => (/* binding */ omnivaVertical)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);


const omnivaVertical = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
  width: "24",
  height: "24",
  viewBox: "0 0 24 24",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
  "fill-rule": "evenodd",
  "clip-rule": "evenodd",
  d: "M19.8823 4.11126V19.8508H4.11774V4.11126H19.8823ZM19.8823 4.11121V2.82046e-05V0C25.3726 5.48167 25.3726 14.3692 19.8823 19.8508H23.9999C18.5096 25.3323 9.60806 25.3325 4.11774 19.8508V23.962C-1.37251 18.4804 -1.37258 9.59298 4.11753 4.11126H4.11774V4.11105C4.11767 4.11112 4.1176 4.11119 4.11753 4.11126H9.54046e-05C5.49038 -1.37034 14.3919 -1.37036 19.8823 4.11121ZM19.8823 4.11121V4.11126H19.8823C19.8823 4.11124 19.8823 4.11122 19.8823 4.11121Z",
  fill: "#FF6600"
}));

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

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

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
/*!********************************************************!*\
  !*** ./src/terminal-selection-block/checkout/index.js ***!
  \********************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./edit */ "./src/terminal-selection-block/checkout/edit.js");
/* harmony import */ var _global_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../global/icons */ "./src/terminal-selection-block/global/icons.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./src/terminal-selection-block/checkout/block.json");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_3__, {
  icon: _global_icons__WEBPACK_IMPORTED_MODULE_2__.omnivaVertical,
  edit: _edit__WEBPACK_IMPORTED_MODULE_1__.Edit,
  attributes: {
    terminal: {
      type: 'string',
      default: '',
      source: 'attribute'
    }
  }
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map