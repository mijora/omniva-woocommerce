<?php
class OmnivaLt_Wc_Blocks
{
    private static $ajax_actions_registered = false;

    public static function register_ajax_actions()
    {
        if ( self::$ajax_actions_registered ) {
            return;
        }

        self::$ajax_actions_registered = true;

        add_action('wp_ajax_omnivalt_get_terminals', array(__CLASS__, 'get_terminals_callback'));
        add_action('wp_ajax_nopriv_omnivalt_get_terminals', array(__CLASS__, 'get_terminals_callback'));
        add_action('wp_ajax_omnivalt_get_dynamic_data', array(__CLASS__, 'get_dynamic_data_callback'));
        add_action('wp_ajax_nopriv_omnivalt_get_dynamic_data', array(__CLASS__, 'get_dynamic_data_callback'));
    }

    public static function get_terminals_callback()
    {
        // This is a public, read-only endpoint used to load terminal data.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No state is changed by this endpoint.
        $country = isset($_GET['country']) ? strtoupper(sanitize_key(wp_unslash($_GET['country']))) : '';
        if ( '' === $country ) {
            wp_send_json_error('Missing country parameter');
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No state is changed by this endpoint.
        $type = isset($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : 'terminal';

        $terminals = OmnivaLt_Terminals::get_terminals_for_map_new($country, $type);
        if ( empty($terminals) || ! is_array($terminals) ) {
            $terminals = array();
        }

        wp_send_json_success($terminals);
    }

    public static function get_dynamic_data_callback()
    {
        // This is a public, read-only endpoint used to load checkout data.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No state is changed by this endpoint.
        $country = isset($_GET['country']) ? strtoupper(sanitize_key(wp_unslash($_GET['country']))) : '';
        if ( '' === $country ) {
            wp_send_json_error('Missing country parameter');
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No state is changed by this endpoint.
        $woo_method_id = isset($_GET['method']) ? sanitize_text_field(wp_unslash($_GET['method'])) : '';
        if ( '' === $woo_method_id ) {
            wp_send_json_error('Missing method parameter');
        }

        $settings = OmnivaLt_Core::get_settings();
        $is_picapac = OmnivaLt_Picapac::is_rate($woo_method_id);
        $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($woo_method_id);
        $terminals_type = $is_picapac ? false : OmnivaLt_Method::get_terminal_type($method_key);
        $omniva_methods = OmnivaLt_Method::get_all();
        $omniva_method_key = ($terminals_type == 'post') ? 'post_specific' : 'pickup';

        if ( empty($omniva_methods[$omniva_method_key]) || ! is_array($omniva_methods[$omniva_method_key]) ) {
            wp_send_json_error('Invalid Omniva delivery method', 400);
        }

        $omniva_method = $omniva_methods[$omniva_method_key];
        $provider = 'omniva';
        $map_icon = isset($omniva_method['map_marker']) ? $omniva_method['map_marker'] : '';
        if ( $country == 'FI' ) {
            $provider = 'matkahuolto';
            if ( isset($omniva_method['display_by_country'][$country]['map_marker']) ) {
                $map_icon = $omniva_method['display_by_country'][$country]['map_marker'];
            }
        }

        $phone_regex = '';
        if ( isset($settings['verify_phone']) && $settings['verify_phone'] === 'yes' ) {
            $phone_regex_raw = OmnivaLt_Helper::get_mobile_regex(strtoupper($country));
            $phone_regex_clean = trim($phone_regex_raw, '/');
            $phone_regex = $phone_regex_clean;
        }

        wp_send_json_success(array(
            'terminals_type' => $terminals_type,
            'provider' => $provider,
            'map_icon' => $map_icon,
            'country' => $country,
            'phone_regex' => $phone_regex
        ));
    }

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
            woocommerce_store_api_register_endpoint_data(array(
                'endpoint' => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
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
        $data = is_array($data) ? $data : array();
        $selected_method = isset($data['selected_rate_id']) && is_string($data['selected_rate_id']) ? wc_clean($data['selected_rate_id']) : '';
        $selected_terminal_id = isset($data['selected_terminal']) && is_scalar($data['selected_terminal']) ? wc_clean((string) $data['selected_terminal']) : '';

        // The session owns the current rate. Extension data can still contain the
        // previous rate during a switch; it is only a fallback without session data.
        $chosen_methods = OmnivaLt_Wc::get_session('chosen_shipping_methods');
        if ( is_array($chosen_methods) && ! empty($chosen_methods) ) {
            $selected_method = '';
            foreach ( $chosen_methods as $chosen_method ) {
                if ( is_string($chosen_method) && strpos($chosen_method, 'omnivalt_') === 0 ) {
                    $selected_method = $chosen_method;
                    break;
                }
            }
        }

        $meta_keys = OmnivaLt_Core::get_configs('meta_keys');
        if ( strpos($selected_method, 'omnivalt_') !== 0 ) {
            $order->delete_meta_data($meta_keys['terminal_id']);
            return;
        }

        $is_picapac = OmnivaLt_Picapac::is_rate($selected_method);
        $is_terminal_method = OmnivaLt_Method::is_omniva_domestic_terminal($selected_method);
        if ( $is_terminal_method ) {
            $country = $order->get_shipping_country();
            if ( '' === $country ) {
                $country = $order->get_billing_country();
            }

            $method_key = OmnivaLt_Omniva_Order::get_method_key_from_id($selected_method);
            $terminal_type = OmnivaLt_Method::get_terminal_type($method_key);
            if ( $is_picapac ) {
                $selected_terminal_id = OmnivaLt_Picapac::get_terminal_id();
                $valid_terminal = OmnivaLt_Picapac::is_supported_country($country);
            } else {
                if ( '' === $selected_terminal_id && isset($_COOKIE['omniva_terminal']) && is_scalar($_COOKIE['omniva_terminal']) ) {
                    $selected_terminal_id = sanitize_text_field(wp_unslash((string) $_COOKIE['omniva_terminal']));
                }
                $valid_terminal = OmnivaLt_Terminals::is_valid_terminal($selected_terminal_id, $country, $terminal_type);
            }

            if ( ! $valid_terminal ) {
                $message = ('post' === $terminal_type) ? __('Please select post office', 'omnivalt') : __('Please select parcel machine', 'omnivalt');
                throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('omnivalt_invalid_terminal', esc_html($message), 400);
            }
        } else {
            // A reused Store API draft may already have terminal metadata.
            $selected_terminal_id = '';
        }

        $method_saved = OmnivaLt_Omniva_Order::set_method($order->get_id(), $selected_method);
        if ( ! $method_saved ) {
            OmnivaLt_Debug::log_error('Failed to save Omniva delivery method from Blocks Checkout. Received method: ' . print_r($selected_method, true));
        }

        if ( '' === $selected_terminal_id ) {
            $order->delete_meta_data($meta_keys['terminal_id']);
            return;
        }

        if ( (string) $order->get_meta($meta_keys['terminal_id'], true) !== $selected_terminal_id ) {
            // Store API saves this same CRUD object after the hook completes.
            $order->update_meta_data($meta_keys['terminal_id'], $selected_terminal_id);
            OmnivaLt_Wc_Order::add_note($order->get_id(), '<b>Omniva:</b> ' . __('Customer choose parcel machine', 'omnivalt') . ' - ' . OmnivaLt_Terminals::get_terminal_address($selected_terminal_id, true) . ' <i>(ID: ' . $selected_terminal_id . ')</i>');
        }
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
        $selected_terminal_id = '';
        $cookie_terminal_id = '';

        if ( isset($_COOKIE['omniva_terminal']) && is_scalar($_COOKIE['omniva_terminal']) ) {
            $cookie_terminal_id = sanitize_text_field(wp_unslash((string) $_COOKIE['omniva_terminal']));
        }

        // A terminal can be changed in the Blocks checkout without a classic
        // checkout request, so its cookie is newer than the saved session value.
        if ( ! empty($cookie_terminal_id) ) {
            $selected_terminal_id = $cookie_terminal_id;
        }

        if ( empty($selected_terminal_id) ) {
            $selected_terminal_id = OmnivaLt_Wc::get_session('omnivalt_terminal_id');
        }

        return array(
            'selected_terminal' => $selected_terminal_id,
            'selected_rate_id' => '',
        );
    }

    public static function cb_schema_callback()
    {
        return array(
            'selected_terminal'  => array(
                'description' => __('Selected terminal', 'omnivalt'),
                'type'        => array('string', 'null'),
                'readonly'    => false,
            ),
            'selected_rate_id'  => array(
                'description' => __('Selected rate ID', 'omnivalt'),
                'type'        => array('string', 'null'),
                'readonly'    => false,
            ),
        );
    }

    public static function is_admin_page_has_block( $block_name )
    {
        if ( ! isset($_GET['post']) ) {
            return false;
        }

        $post_id = absint($_GET['post']);
        $post = get_post( $post_id );

        if ( ! $post || ! has_blocks( $post ) ) {
            return false;
        }

        $blocks = parse_blocks( $post->post_content );

        foreach ( $blocks as $block ) {
            if ( isset($block['blockName']) && $block['blockName'] === $block_name ) {
                return true;
            }
        }

        return false;
    }

    public static function is_front_page_has_block( $block_name ) {
        $post = get_queried_object();

        if ( ! $post instanceof WP_Post ) {
            return false;
        }

        if ( has_block( $block_name, $post ) ) {
            return true;
        }

        if ( ! has_blocks( $post ) ) {
            return false;
        }

        $blocks = parse_blocks( $post->post_content );
        return self::search_blocks_recursive( $blocks, $block_name );
    }

    private static function search_blocks_recursive( $blocks, $block_name ) {
        foreach ( $blocks as $block ) {
            if ( isset( $block['blockName'] ) && $block['blockName'] === $block_name ) {
                return true;
            }
            if ( ! empty( $block['innerBlocks'] ) ) {
                if ( self::search_blocks_recursive( $block['innerBlocks'], $block_name ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function is_cart_page_editor()
    {
        return (self::is_admin_page_has_block('woocommerce/cart'));
    }

    public static function is_checkout_page_editor()
    {
        return (self::is_admin_page_has_block('woocommerce/checkout'));
    }

    public static function is_cart_page()
    {
        return (self::is_front_page_has_block('woocommerce/cart'));
    }

    public static function is_checkout_page()
    {
        return (self::is_front_page_has_block('woocommerce/checkout'));
    }
}
