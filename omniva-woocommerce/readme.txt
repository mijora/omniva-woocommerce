== Explanations of errors ==

001 'The service is not available to the sender country' - There is no desired key in configs 'shipping_params' section
002 'The service is not available to the receiver country' - There is no desired key in configs 'shipping_params' section 'shipping_sets' subsection
003 'This shipping set does not exist' - There is no desired key in configs 'shipping_sets' section
004 'The service does not exist for the specified method' - There is no desired key in configs 'shipping_sets' section subsection

== Hooks ==
omnivalt_label_register_successfully - args: (integer) $order_id - shipment successfully registered
omnivalt_label_register_failed - args: (integer) $order_id - failed to register shipment

== Filters ==
omnivalt_orders_list_per_page - args: (integer) $per_page - allows to change the number of orders displayed on the Omniva Orders page. Default value: 25.
omnivalt_settings_coupons_args - args: (array) $args - can change the attributes by which receiving list of coupons.
