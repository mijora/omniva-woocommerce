== Changelog ==

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
 
