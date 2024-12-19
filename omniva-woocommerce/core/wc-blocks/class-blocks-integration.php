<?php
use \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class Omnivalt_Blocks_Integration implements IntegrationInterface
{
    private $version = '1.0.0';

    private function get_scripts_dir()
    {
        return OMNIVALT_DIR . 'assets/blocks/';
    }

    private function get_scripts_url()
    {
        return OMNIVALT_URL . 'assets/blocks/';
    }

    /**
     * The name of the integration.
     *
     * @return string
     */
    public function get_name() {
        return 'omnivalt-blocks';
    }

    /**
     * Initial integration
     */
    public function initialize() {
        require_once OmnivaLt_Core::get_core_dir() . 'wc-blocks/class-blocks-extend-store-endpoint.php';
        $this->register_external_scripts();
        $this->register_block_frontend_scripts();
        $this->register_block_editor_scripts();
        $this->register_main_integration();
        $this->register_additional_actions();
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @return string[]
     */
    public function get_script_handles() {
        return array('omnivalt-blocks-integration', 'omnivalt-block-frontend-checkout', 'omnivalt-block-frontend-cart');
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles() {
        return array('omnivalt-block-edit-checkout', 'omnivalt-block-edit-cart');
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data() {
        $omniva_settings = get_option(\OmnivaLt_Core::get_configs('plugin')['settings_key']);
        $show_map = (isset($omniva_settings['show_map']) && $omniva_settings['show_map'] == 'yes') ? true : ((! isset($omniva_settings['show_map'])) ? true : false);
        $autoselect = (isset($omniva_settings['auto_select']) && $omniva_settings['auto_select'] == 'yes') ? true : false;
        if ( ! isset($omniva_settings['auto_select']) ) $autoselect = true; //Enable by default
        $debug_mode = (isset($omniva_settings['debug_front_js']) && $omniva_settings['debug_front_js'] == 'yes') ? true : false;
        
        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => OMNIVALT_URL,
            'methods' => array(
                'terminal_omniva' => 'omnivalt_pt',
                'terminal_matkahoulto' => 'omnivalt_pt',
                'post_omniva' => 'omnivalt_ps',
                'letter_post_omniva' => 'omnivalt_lp'
            ),
            'show_map' => $show_map,
            'autoselect' => $autoselect,
            'debug' => $debug_mode,
            'txt' => array(
                'block_options' => __('Block options', 'omnivalt'),
                'title_terminal' => __('Parcel terminal', 'omnivalt'),
                'select_terminal' => __('Select parcel terminal', 'omnivalt'),
                'error_terminal' => __('Please select parcel terminal', 'omnivalt'),
                'cart_terminal_info' => __('You can choose the parcel terminal on the Checkout page', 'omnivalt'),
                'loading_field' => __('Loading select field...', 'omnivalt'),
                'title_post' => __('Post office', 'omnivalt'),
                'select_post' => __('Select post office', 'omnivalt'),
                'error_post' => __('Please select post office', 'omnivalt'),
                'cart_post_info' => __('You can choose the post office on the Checkout page', 'omnivalt'),
                'providers' => array(
                    'omniva' => __('Omniva', 'omnivalt'),
                    'matkahuolto' => __('Matkahuolto', 'omnivalt')
                ),
                'map' => array(
                    'modal_title_post' => __('post offices', 'omnivalt'),
                    'modal_title_terminal' => __('parcel terminals', 'omnivalt'),
                    'modal_search_title_post' => __('Post offices list', 'omnivalt'),
                    'modal_search_title_terminal' => __('Parcel terminals list', 'omnivalt'),
                    'select_post' => __('Select post office', 'omnivalt'),
                    'select_terminal' => __('Select terminal', 'omnivalt'),
                    'search_placeholder' => __('Enter postcode', 'omnivalt'),
                    'search_button' => __('Search', 'omnivalt'),
                    'select_button' => __('Select', 'omnivalt'),
                    'modal_open_button' => __('Select in map', 'omnivalt'),
                    'use_my_location' => __('Use my location', 'omnivalt'),
                    'my_position' => __('Distance calculated from this point', 'omnivalt'),
                    'not_found' => __('Place not found', 'omnivalt'),
                    'no_cities_found' => __('There were no cities found for your search term', 'omnivalt'),
                    'geo_not_supported' => __('Geolocation is not supported', 'omnivalt')
                ),
                'select' => array(
                    'not_found' => __('Place not found', 'omnivalt'),
                    'search_too_short' => __('Value is too short', 'omnivalt'),
                    'terminal_select' => __('Select terminal', 'omnivalt'),
                    'terminal_map_title' => __('parcel terminals', 'omnivalt'),
                    'terminal_map_search_title' => __('Parcel terminals addresses', 'omnivalt'),
                    'post_select' => __('Select post office', 'omnivalt'),
                    'post_map_title' => __('post offices', 'omnivalt'),
                    'post_map_search_title' => __('Post offices addresses', 'omnivalt'),
                    'enter_address' => __('Enter postcode/address', 'omnivalt'),
                    'show_in_map' => __('Show in map', 'omnivalt'),
                    'show_more' => __('Show more', 'omnivalt')
                )
            ),
        );
    }

    public function register_block_frontend_scripts() {
        $scripts = array(
            'omnivalt-block-frontend-checkout' => array(
                'js' => 'terminal-selection-block/checkout/frontend.js',
                'asset' => 'terminal-selection-block/checkout/frontend.asset.php',
                'css' => 'terminal-selection-block/checkout/frontend.css'
            ),
            'omnivalt-block-frontend-cart' => array(
                'js' => 'terminal-selection-block/cart/frontend.js',
                'asset' => 'terminal-selection-block/cart/frontend.asset.php',
            ),
        );

        $this->register_scripts($scripts);
    }

    public function register_block_editor_scripts() {
        $scripts = array(
            'omnivalt-block-edit-checkout' => array(
                'js' => 'terminal-selection-block/checkout/index.js',
                'asset' => 'terminal-selection-block/checkout/index.asset.php',
            ),
            'omnivalt-block-edit-cart' => array(
                'js' => 'terminal-selection-block/cart/index.js',
                'asset' => 'terminal-selection-block/cart/index.asset.php',
            ),
        );

        $this->register_scripts($scripts);
    }

    private function register_scripts( $scripts_list )
    {
        foreach ( $scripts_list as $script_id => $script_files ) {
            if ( ! isset($script_files['js']) || ! isset($script_files['asset']) ) {
                continue;
            }
            $script_url = $this->get_scripts_url() . $script_files['js'];
            $script_asset_path = $this->get_scripts_dir() . $script_files['asset'];

            $script_asset = file_exists($script_asset_path) ? require $script_asset_path : array(
                'dependencies' => array(),
                'version' => $this->get_file_version($script_asset_path),
            );

            wp_register_script(
                $script_id,
                $script_url,
                $script_asset['dependencies'],
                $script_asset['version'],
                true
            );

            if ( isset($script_files['translations']) ) {
                wp_set_script_translations(
                    $script_id,
                    $script_files['translations'],
                    OMNIVALT_DIR . '/languages'
                );
            }

            if ( isset($script_files['css']) ) {
                $style_url = $this->get_scripts_url() . $script_files['css'];
                $style_path = $this->get_scripts_dir() . $script_files['css'];

                wp_enqueue_style(
                    $script_id,
                    $style_url,
                    [],
                    $this->get_file_version($style_path)
                );
            }
        }
    }

    private function register_main_integration()
    {
        $script_path = $this->get_scripts_dir() . 'index.js';
        $style_path  = $this->get_scripts_dir() . 'style-index.css';

        $script_url = $this->get_scripts_url() . 'index.js';
        $style_url  = $this->get_scripts_url() . 'style-index.css';

        $script_asset_path = $this->get_scripts_dir() . 'index.asset.php';

        $scripts = array(
            'omnivalt-blocks-integration' => array(
                'js' => 'index.js',
                'asset' => 'index.asset.php',
                //'css' => 'style-index.css',
                'translations' => 'omnivalt'
            ),
        );

        $this->register_scripts($scripts);
    }

    public function register_additional_actions()
    {
        add_action('wp_ajax_omnivalt_get_terminals', array($this, 'get_terminals_callback'));
        add_action('wp_ajax_nopriv_omnivalt_get_terminals', array($this, 'get_terminals_callback'));
        add_action('wp_ajax_omnivalt_get_dynamic_data', array($this, 'get_dynamic_data_callback'));
        add_action('wp_ajax_nopriv_omnivalt_get_dynamic_data', array($this, 'get_dynamic_data_callback'));
    }

    public function get_terminals_callback()
    {
        if ( empty($_GET['country']) ) {
            wp_send_json_error('Missing country parameter');
            return;
        }

        $country = esc_attr($_GET['country']);
        $type = (! empty($_GET['type'])) ? esc_attr($_GET['type']) : 'terminal';

        $terminals = \OmnivaLt_Terminals::get_terminals_for_map_new($country, $type);
        if ( empty($terminals) || ! is_array($terminals) ) {
            $terminals = array();
        }
        
        wp_send_json_success($terminals);
    }

    public function get_dynamic_data_callback()
    {
        if ( empty($_GET['country']) ) {
            wp_send_json_error('Missing country parameter');
            return;
        }
        if ( empty($_GET['method']) ) {
            wp_send_json_error('Missing method parameter');
            return;
        }

        $country = esc_attr($_GET['country']);
        $woo_method_id = esc_attr($_GET['method']);

        $method_key = \OmnivaLt_Omniva_Order::get_method_key_from_id($woo_method_id);
        $terminals_type = \OmnivaLt_Method::get_terminal_type($method_key);
        $omniva_methods = \OmnivaLt_Method::get_all();
        $omniva_method = ($terminals_type == 'post') ? $omniva_methods['post_specific'] : $omniva_methods['pickup'];

        $provider = 'omniva';
        $map_icon = $omniva_method['map_marker'];
        if ( $country == 'FI' ) {
            $provider = 'matkahuolto';
            $map_icon = $omniva_method['display_by_country'][$country]['map_marker'];
        }

        wp_send_json_success(array(
            'terminals_type' => $terminals_type,
            'provider' => $provider,
            'map_icon' => $map_icon,
        ));
    }

    public function register_external_scripts()
    {
        $js_url = OMNIVALT_URL . 'assets/js/';
        $css_url = OMNIVALT_URL . 'assets/css/';

        $scripts = array(
            'omnivalt-library-mapping' => array(
                'js' => 'terminal-mapping.js',
                'css' => 'terminal-mapping.css'
            ),
            'omnivalt-library-leaflet' => array(
                'js' => 'leaflet.js',
                'css' => 'leaflet.css'
            ),
        );

        foreach ( $scripts as $script_id => $script_files ) {
            if ( ! empty($script_files['js']) ) {
                wp_enqueue_script($script_id, $js_url . $script_files['js'], array('jquery'), null, true);
            }
            if ( ! empty($script_files['css']) ) {
                wp_enqueue_style($script_id, $css_url . $script_files['css']);
            }
        }
    }

    /**
     * Extends the cart schema to include the shipping-workshop value.
     */
    private function extend_store_api()
    {
        Omnivalt_Blocks_Extend_Store_Endpoint::init();
    }

    /**
     * Get the file modified time as a cache buster if we're in dev mode.
     *
     * @param string $file Local path to the file.
     * @return string The cache buster value to use for the given file.
     */
    private function get_file_version( $file )
    {
        if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file) ) {
            return filemtime($file);
        }
        
        return $this->version;
    }
}
