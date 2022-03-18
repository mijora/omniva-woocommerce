== Explanations of errors ==

001 'The service is not available to the sender country' - There is no desired key in configs 'shipping_params' section
002 'The service is not available to the receiver country' - There is no desired key in configs 'shipping_params' section 'shipping_sets' subsection
003 'This shipping set does not exist' - There is no desired key in configs 'shipping_sets' section
004 'The service does not exist for the specified method' - There is no desired key in configs 'shipping_sets' section subsection

== Changelog ==

= 1.10.1 =
- fixed Omniva shipping class multi call
- fixed and renamed manifest page
- added separation between delivery methods and delivery countries in settings page
- added parameter "SameSite" for cookies when creating cookie via JavaSript
- fixed mistake in COD code
- separated plugin CSS animations

= 1.10.0 =
- fixed and renamed manifest page
- added separation between delivery methods and delivery countries in settings page
- added parameter "SameSite" for cookies when creating cookie via JavaSript
- fixed mistake in COD code
- separated plugin CSS animations
- added option to choose label design for delivery methods
- added orders selection from multiple pages in Omniva Shipping page
- labels are no longer stored in the plugin folder for a long time
- added a ability to set custom shipping method name
- updated Estonian translations
- improved for PHP 8.0

= 1.9.2.1 =
- fixed terminal selection field load
- fixed free shipping with coupons

= 1.9.2 =
- fixed bug, when terminal locations file not exists
- changed maximum width of the call courier popup window
- fixed free shipping
- added the number of parcels when a courier is called
- improved plugin security

= 1.9.1 =
- fixed PHP errors, when new settings is not saved
- moved vendors to separate folder
- added a option to disable tracking link showing in Woocommerce emails
- the settings page is organized into sections
- excluded locations.json file in install zip to generate the latest terminals list during each installation
- images moved to separate folder

= 1.9.0 =
- added terminal price set by terminal size
- redesigned some features
- adapted for use with Estonian API logins
- added new destination country: Finland
- added new shipping methods: Post office, Private customer, Courier Plus
- added support of additional services
- improved debug working
- added update checker
- added "Bad API logins" error when failed generate label because of authentication
- added a ability to add comment in label
- fixed bug with country in checkout page
- added fix, which select shop country if country is not set in order
- created global errors with codes

= 1.8.4 =
- fixed terminal selection field showing when country is changed and Omniva pickup become selected
- fixed compatibility with old version plugin settings

= 1.8.3 =
- fixed terminal validation function when required POST element not exists
- added a ability to disable Omniva shipping method for specific categories

= 1.8.2 =
- added a ability to set shipping method description

= 1.8.1 =
- added a ability to set shipping price by cart weight or cart amount

= 1.8.0 =
- fixed shipping method check for order
- fixed terminal autoselect when shipping address is not entered
- added max weight option to courier
- created a ability to use coupon for free shipping

= 1.7.2 =
- fixed shipping methods display compatibility function
- fixed terminal selection when changed country
- added phone number pick from shipping field
- added additional display:none style for omniva script tag to avoid javascript displaying in terminal selection

= 1.7.1 =
- fixed terminal selection field in order edit

= 1.7.0 =
- fixed order ID in COD
- improved terminal selection field working in cart and checkout pages
- improved prices settings structure and Omniva shipping methods displaying
- created a ability to choose labels printing type (single label or 4 labels in a PDF page)
- created a ability to change terminal in admin order edit page

= 1.6.2 =
- fixed JS in Checkout when Omniva Shipping method is not displayed
- fixed bulk action in orders list
- fixed "id was called incorrectly" notice when WP logging is enabled
- merged debug settings to one option
- added debug of response in WP notice message when debug is activated

= 1.6.1 =
- added the ability to debug request and response to/from Omniva server
- fixed size values get from products
- fixed API url using, when slash exists at the end of string

= 1.6.0 =
- fixed that shipping prices lower than 0 could not be entered
- fixed that shipping does not become free when the value "free from" is not entered
- added cleanup of strings to all xml request strings
- fixed terminal auto selection
- created the ability to turn off the specific shipping method when the price is not specified
- created settings display by checked checkboxes
- improved settings page programmic code
- created an automatic email sending with a tracking number to customer when shipping label is created (generated tracking number)
- created manifest translation into other languages
- added settings button in plugins list
- added more information to plugin description
- added option to disable parcel terminal selection if cart size is to big
- updated settings display
- created ability, to take client name from billing section, when client name in shipping section is not specified
- added option to send email to customer, when the shipment arrive to terminal
- changed settings variable names. Required to update price settings

= 1.5.11 =
- added language files by Wordpress 5.5 locales

= 1.5.10 =
- fixed text display in the table column "Service" on the Omniva manifest page
- added cleanup of strings received from order
- added option to turn off automatic terminal selection

= 1.5.9 =
- Fixed terminal select field working in Cart page

= 1.5.8 =
- Updated PDF libraries
- Omniva manifest page now requires manage_woocommerce permission to be shown

= 1.5.7 =
- Fixes label error display bug in manifest page

= 1.5.6 =
- Fixes to support Woocommerce 4.0.0
- Workaround to how terminals stringification is done - as in rare cases PHP stringified json becomes invalid after reaching client.

= 1.5.5 =
- Adds moment.min.js as not all woocommerce installations has it.

= 1.5.4 =
- Fixed tracking number display in various places (no longer printed if there is no number)
- Weight limit settings works again, to disable leave empty or 0.

= 1.5.3 =
- Updated map tile server url

= 1.5.2 =
- Tracking number display in emails, order details (both admin and user)
- Locations update checks that its valid JSON and only then approves old file replace

= 1.5.1 =
- Updated TCPDF to 6.3.2.
- Fixed persisting pagination between Omniva Manifest page tabs.
- Improved order number display in Omniva Manifest page.

= 1.5.0 =
- Fully reworked Omniva Manifest page.
- Improved detection of changed postcode in checkout.
- Updated TCPDF and FPDI libraries.
- Localization update.

= 1.4.13 =
- Parcel terminal address in email and order details view.
- Call courier functionality (in Omniva Manifest), no more automatically called courier.
- Localization update.
- Omniva manifest page improvements.

= 1.4.12 =
- Map functionality, bug fixes

= 1.4.3 =
- Bug fix - filtered post offices from list

= 1.4.2 =
- Bug fix in debug

= 1.4.1 =
- Added WooCommerce 3.4.1 support
 
= 1.3.3 =
- Added selected parcel terminal in admin order view.
- Add parcel terminal validation on checkout.
 
= 1.3.2 =
- Bugs fixed.
- Allowed to generate manifest more than once.
