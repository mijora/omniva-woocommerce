== Changelog ==

= 1.5.9 =
* Fixed terminal select field working in Cart page

= 1.5.8 =
* Updated PDF libraries
* Omniva manifest page now requires manage_woocommerce permission to be shown

= 1.5.7 =
* Fixes label error display bug in manifest page

= 1.5.6 =
* Fixes to support Woocommerce 4.0.0
* Workaround to how terminals stringification is done - as in rare cases PHP stringified json becomes invalid after reaching client.

= 1.5.5 =
* Adds moment.min.js as not all woocommerce installations has it.

= 1.5.4 =
* Fixed tracking number display in various places (no longer printed if there is no number)
* Weight limit settings works again, to disable leave empty or 0.

= 1.5.3 =
* Updated map tile server url

= 1.5.2 =
* Tracking number display in emails, order details (both admin and user)
* Locations update checks that its valid JSON and only then approves old file replace

= 1.5.1 =
* Updated TCPDF to 6.3.2.
* Fixed persisting pagination between Omniva Manifest page tabs.
* Improved order number display in Omniva Manifest page.

= 1.5.0 =
* Fully reworked Omniva Manifest page.
* Improved detection of changed postcode in checkout.
* Updated TCPDF and FPDI libraries.
* Localization update.

= 1.4.13 =
* Parcel terminal address in email and order details view.
* Call courier functionality (in Omniva Manifest), no more automatically called courier.
* Localization update.
* Omniva manifest page improvements.

= 1.4.12 =
* Map functionality, bug fixes

= 1.4.3 =
* Bug fix - filtered post offices from list

= 1.4.2 =
* Bug fix in debug

= 1.4.1 =
* Added WooCommerce 3.4.1 support
 
= 1.3.3 =
* Added selected parcel terminal in admin order view.
* Add parcel terminal validation on checkout.
 
= 1.3.2 =
* Bugs fixed.
* Allowed to generate manifest more than once.
 
