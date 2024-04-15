<?php
use \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class Omnivalt_Blocks_Integration implements IntegrationInterface
{
    private $version = '0.0.1';

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
        $debug_mode = (isset($omniva_settings['debug_mode']) && $omniva_settings['debug_mode'] == 'yes') ? true : false;
        
        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => OMNIVALT_URL,
            'methods' => array(
                'terminal_omniva' => 'omnivalt_pt',
                'terminal_matkahoulto' => 'omnivalt_pt',
                'post_omniva' => 'omnivalt_ps',
            ),
            'show_map' => $show_map,
            'debug' => $debug_mode,
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

    private function build_terminals_list( $terminals, &$prepared_list ) //TODO: Galbut nereiks
    {
        foreach ( $terminals as $terminal ) {}
        foreach ( $terminals_group as $group => $group_values ) {
            if ( ! is_array($group_values) ) {
                $prepared_list[] = array('label' => $group_values, 'value' => $group);
                continue;
            }
            $this->build_terminals_list($group_values, $prepared_list);
        }
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
        $terminals_type = \OmnivaLt_Configs::get_method_terminals_type($method_key);
        $omniva_methods = OmnivaLt_Core::get_configs('method_params_new');
        $omniva_method = ($terminals_type == 'post') ? $omniva_methods['post_specific'] : $omniva_methods['terminal'];

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
