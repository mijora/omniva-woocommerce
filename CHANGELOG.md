# Changelog

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
