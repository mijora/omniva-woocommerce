<?php
class OmnivaLt_Wc_Blocks
{
    public static function init()
    {
        require_once OmnivaLt_Core::get_core_dir() . 'wc-blocks/class-blocks-integration.php';

        add_action('woocommerce_blocks_checkout_block_registration', function( $integration_registry ) {
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
    }

    public static function register_block_categories( $categories )
    {
        return array_merge(
            $categories,
            [
                [
                    'slug'  => 'omnivalt',
                    'title' => __( 'Omniva Blocks', 'omnivalt' ),
                ],
            ]
        );
    }

    public static function cb_data_callback()
    {
        return array(
            'abc' => '',
        );
    }

    public static function cb_schema_callback()
    {
        return array(
            'properties' => array(
                'abc'  => array(
                    'description' => __( 'Gift Message', 'omnivalt' ),
                    'type'        => array( 'string', 'null' ),
                    'readonly'    => true,
                ),
            )
        );
    }
}
