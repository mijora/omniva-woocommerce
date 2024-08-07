== Explanations of errors ==

001 'The service is not available to the sender country' - There is no desired key in configs 'shipping_params' section
002 'The service is not available to the receiver country' - There is no desired key in configs 'shipping_params' section 'shipping_sets' subsection
003 'This shipping set does not exist' - There is no desired key in configs 'shipping_sets' section
004 'The service does not exist for the specified method' - There is no desired key in configs 'shipping_sets' section subsection

== Hooks ==
omnivalt_label_register_successfully - args: $order_id - shipment successfully registered
omnivalt_label_register_failed - args: $order_id - failed to register shipment

== Filters ==
omnivalt_orders_list_per_page - args: $per_page - allows to change the number of orders displayed on the Omniva Orders page. Default value: 25.

== Changelog ==

= 1.17.0 =
- added changes based on updated Omniva API OMX library
- added the possibility to log the data submitted from the Checkout page to the plugin
- improved adding Omniva data to the Order when used the Checkout page does not pass Omniva data through the POST element
- updated Omniva API library to v1.1.0

= 1.16.1 =
- fixed terminal autoselect on old Checkout page

= 1.16.0 =
- disabled phone format validation, when selected shipping method is not Omniva
- created a parcel terminal selection on the block-based Checkout page
- added translations of the plugin into Latvian and Estonian languages

= 1.15.8 =
- fixed map load on Cart page when the updated_wc_div event fires
- fixed to check if the cart is not empty before creating shipping methods
- added possibility to register multi-parcels shipments (MPS)
- added the option to specify how many MPS shipments it needs for the product
- updated Omniva API library to v1.0.17

= 1.15.7 =
- fixed JS error on Cart page (due to the missing postcode field)
- fixed JS error on Checkout page (due to a empty line in the script code)
- fixed an error occurring during customer registration
- added the option to specify a landline phone number in the sender (shop) settings, if no mobile phone number is entered
- created an error display in the admin area when the plugin cannot work

= 1.15.6 =
- fixed set of API type
- fixed adding the tracking link to the email when the order status is changed outside of the order edit page
- added the ability to translate the text of the "Select" button displayed on the map
- added an option to activate the format validation of the entered phone number on the Checkout page
- added possibility for Latvian Omniva clients to send parcels to Finland parcel terminals
- added a new WP filter that allows to change the number of orders displayed on the Omniva Orders page

= 1.15.5 =
- reworked getting settings to avoid an error when the required element is not among the saved settings
- fixed error when session is null
- changed Finland terminals provider from Omniva to Matkahuolto

= 1.15.4 =
- fixed "out of memory" error when the website has too many coupons
- removed COD payments for Finland Matkahulto terminals: Default WooCommerce COD
- added LT translations of texts created during the last updates

= 1.15.3 =
- added new hook omnivalt_label_register_successfully which is fired when label successfully generated
- added new hook omnivalt_label_register_failed which is fired when failed to generate label
- updated Omniva API library to v1.0.15

= 1.15.2 =
- added temporary fix for order size calculation
- started using terminal-mapping JS library for map

= 1.15.1 =
- fixed var directory creating
- moved locations.json file to var/locations directory
- added Matkahulto parcel terminals for Finland
- completed the operation of the courier call for the OMX system
- adapted so that the module could work with different API libraries
- changed terminals source URL
- improved display of shipping methods depending on API country
- improved part of JS code in plugin settings page
- updated Omniva API library to v1.0.14

= 1.15.0 =
- fixed error when changing Order status to "Refunded"
- fixed Omniva data saving every time even though the data is not changed
- fixed Omniva additional services adding to the shipment
- fixed error when not exists get_current_screen() function
- created API library usage for all API functions
- created the ability to see and cancel courier invitations (works only on OMX system)
- improved division of orders in the tabs on the "Omniva shipping" page
- added new tabs "Registered orders" and "Orders ready to ship" on the "Omniva shipping" page
- changed to display the courier call success message using the default Wordpress message
- added arrival time display to courier call success notification
- added the option in product to mark an additional service "Issue to persons at the age of 18+"
- updated Omniva API library to v1.0.13

= 1.14.2 =
- fixed error when Order create manualy from admin area
- fixed pagination in Omniva Orders list
- added the ability to specify via URL how many orders will be displayed on one page in the Omniva order list

= 1.14.1 =
- moved cronjobs operation to Woocommerce cronjobs
- created a stop of plugin activation if the website does not have permission to create files in the plugin folder
- fixed error when product in Order is permanently deleted
- fixed Orders display in manifest page

= 1.14.0 =
- the plugin is adapted to work with Woocommerce HPOS (prepared for Woocommerce 8)

= 1.13.1 =
- fixed barcodes show in Manifest PDF
- changed map server
- added the option to specify the size of the shipment
- added LT and LV translations for latest changes

= 1.13.0 =
- fixed courier call
- added display of an error message when a certain type of error message is received from the API
- added getting of shipping classes for all languages when using the WPML plugin
- added a ability to the Order add Omniva method manually, when Omniva shipping method is selected, but Omniva metadata not added
- added possibility to send shipments to Estonian post offices
- started using a separate API library
- added many LV translations, missing LT translations and updated EE translations
- added logging when unable to get Omniva data when creating an Order

= 1.12.3 =
- fixed custom positions, when enter same value for multiple methods
- fixed plugin loading on multistore
- fixed PHP notifications, when shipping classes not exists
- fixed tracking URL in emails when in order shipping country is not set
- in customer "Created label" email template added new variables: name, fullname, company
- added company showing in "Omniva orders" page

= 1.12.2 =
- fixed incorrect variable name used for client name

= 1.12.1 =
- fixed the use of coupons when they contain unicode characters
- removed customer phone tag in request, when shop phone is not set
- in the Wordpress dashboard menu bar, the Omniva orders page menu item has been moved below the Woocommerce Orders item
- fixed restricted_shipclass notice, when this field not exists in saved settings
- fixed error in some situation when shipping rates list is empty
- made the error message more accurate when the shipping-delivery method is not allowed
- added the option to specify if to send the return code to the customer
- added additional Omniva terminal saving if failed to save from first time
- added the customer company name using on the labels when the customer name is empty
- added some translations to Latvian

= 1.12.0 =
- added a ability to disable Omniva shipping methods for specific shipping class
- improved additional services working
- corrected the Latvian translation of the phrase "Select terminal"
- fixed "Arrival email" additional service
- added store email get from WP general info when no email is specified in plugin settings
- disabled display of the return code in the delivery SMS
- changed weight units to fixed value used in the plugin
- added phone number service to courier-courier shipping
- fixed label note variables

= 1.11.1 =
- fixed error, when cart product dont have category (product category is "Uncategorized")
- added support for new Omniva XML response namespace
- improved shipping methods restrictions check

= 1.11.0 =
- fixed additional services, for which required phone or email params
- changed sender country to api country when getting services in label generation
- added arrival SMS service to PK shipping service
- rearranged files
- added automatic post code fix by selected country
- improved custom changes show
- added a option to choose shipping methods position

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
- Fixed terminal name generation based on updated omniva locations file

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
