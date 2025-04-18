<?php
class OmnivaLt_Wc_Blocks
{
    public static function init()
    {
        require_once OmnivaLt_Core::get_core_dir() . 'wc-blocks/class-blocks-integration.php';

        add_action('woocommerce_blocks_checkout_block_registration', function( $integration_registry ) {
            $integration_registry->register( new Omnivalt_Blocks_Integration() );
        });
        add_action('woocommerce_blocks_cart_block_registration', function( $integration_registry ) {
            $integration_registry->register( new Omnivalt_Blocks_Integration() );
        });

        if ( function_exists('woocommerce_store_api_register_endpoint_data') ) {
            woocommerce_store_api_register_endpoint_data(array(
                'endpoint' => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
                'namespace' => 'omnivalt',
                'data_callback' => 'OmnivaLt_Wc_Blocks::cb_data_callback',
                'schema_callback' => 'OmnivaLt_Wc_Blocks::cb_schema_callback',
                'schema_type' => ARRAY_A,
            ));
        }
        add_action('woocommerce_store_api_checkout_update_order_from_request', array('OmnivaLt_Wc_Blocks', 'update_block_order_meta'), 10, 2);

        add_filter(
            '__experimental_woocommerce_blocks_add_data_attributes_to_namespace',
            function ( $allowed_namespaces ) {
                $allowed_namespaces[] = 'omnivalt';
                return $allowed_namespaces;
            },
            10,
            1
        );
    }

    public static function update_block_order_meta($order, $request)
    {
        $data = $request['extensions']['omnivalt'] ?? array();

        $selected_method = wc_clean($data['selected_rate_id'] ?? '');
        $selected_terminal_id = wc_clean($data['selected_terminal'] ?? '');

        OmnivaLt_Omniva_Order::set_method($order->get_id(), $selected_method);
        OmnivaLt_Omniva_Order::set_terminal_id($order->get_id(), $selected_terminal_id);
        OmnivaLt_Wc_Order::add_note($order->get_id(), '<b>Omniva:</b> ' . __('Customer choose parcel terminal', 'omnivalt') . ' - ' . OmnivaLt_Terminals::get_terminal_address($selected_terminal_id,true) . ' <i>(ID: ' . $selected_terminal_id . ')</i>');
    }

    public static function register_block_categories( $categories )
    {
        return array_merge(
            $categories,
            [
                [
                    'slug'  => 'omnivalt',
                    'title' => __('Omniva Blocks', 'omnivalt'),
                ],
            ]
        );
    }

    public static function cb_data_callback()
    {
        return array(
            'selected_terminal' => '',
            'selected_rate_id' => '',
        );
    }

    public static function cb_schema_callback()
    {
        return array(
            'selected_terminal'  => array(
                'description' => __('Selected terminal', 'omnivalt'),
                'type'        => array('string', 'null'),
                'readonly'    => true,
            ),
            'selected_rate_id'  => array(
                'description' => __('Selected rate ID', 'omnivalt'),
                'type'        => array('string', 'null'),
                'readonly'    => true,
            ),
        );
    }
}
