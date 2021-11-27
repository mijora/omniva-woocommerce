# Changelog

## [Unreleased]
### Fixed
- fixed bug with country in checkout page

### Improved
- improved debug working
- added "Bad API logins" error when failed generate label because of authentication
- added a ability to add comment in label
- added terminal price set by terminal size
- redesigned some features

### Added
- added update checker
- added new country: Finland
- added new shipping method: Post office
- added new services: Fragile, Delivery to private customer, Document return

## [1.8.4]
### Fixed
- fixed terminal selection field showing when country is changed and Omniva pickup become selected
- fixed compatibility with old version plugin settings

## [1.8.3]
### Fixed
- fixed terminal validation function when required POST element not exists

### Improved
- added a ability to disable Omniva shipping method for specific categories

## [1.8.2]
### Improved
- added a ability to set shipping method description

## [1.8.1]
### Improved
- added a ability to set shipping price by cart weight or cart amount

## [1.8.0]
### Fixed
- fixed shipping method check for order
- fixed terminal autoselect when shipping address is not entered

### Improved
- added max weight option to courier
- created a ability to use coupon for free shipping

## [1.7.2]
### Fixed
- fixed shipping methods display compatibility function
- fixed terminal selection when changed country

### Improved
- added phone number pick from shipping field
- added additional display:none style for omniva script tag to avoid javascript displaying in terminal selection

## [1.7.1]
### Fixed
- fixed terminal selection field in order edit

## [1.7.0]
### Fixed
- fixed order ID in COD

### Improved
- improved terminal selection field working in cart and checkout pages
- improved prices settings structure and Omniva shipping methods displaying
- created a ability to choose labels printing type (single label or 4 labels in a PDF page)
- created a ability to change terminal in admin order edit page

## [1.6.2]
### Fixed
- fixed JS in Checkout when Omniva Shipping method is not displayed
- fixed bulk action in orders list
- fixed "id was called incorrectly" notice when WP logging is enabled

### Improved
- merged debug settings to one option
- added debug of response in WP notice message when debug is activated

## [1.6.1]
### Fixed
- fixed size values get from products
- fixed API url using, when slash exists at the end of string

### Improved
- added the ability to debug request and response to/from Omniva server

## [1.6.0]
### Fixed
- fixed that shipping prices lower than 0 could not be entered
- fixed that shipping does not become free when the value "free from" is not entered
- added cleanup of strings to all xml request strings
- fixed terminal auto selection

### Improved
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

### Changed
- settings variable names. Required to update price settings

## [1.5.11]
### Added
- added language files by Wordpress 5.5 locales

## [1.5.10]
### Fixed
- fixed text display in the table column "Service" on the Omniva manifest page

### Added
- added cleanup of strings received from order
- added option to turn off automatic terminal selection

## [1.5.9]
### Fixed
- fixed terminal select field working in Cart page
- fixed terminal name generation based on updated omniva locations file
