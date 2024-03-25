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
        return array('omnivalt-blocks-integration', 'omnivalt-block-frontend');
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles() {
        return array( 'omnivalt-block-editor' );
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data() {
        return array(
            'methods' => array(
                'terminal_omniva' => 'omnivalt_pt',
                'terminal_matkahoulto' => 'omnivalt_pt',
            )
        );
        //TODO: Galbut prireiks
        $customer = \WC()->session->get('customer');
        $country = (!empty($customer['shipping_country'])) ? $customer['shipping_country'] : ((!empty($customer['country'])) ? $customer['country'] : 'LT');

        \culog(\OmnivaLt_Terminals::get_terminals_list($country, 'terminal'), 'OmniCheck');
        $terminals = \OmnivaLt_Terminals::get_terminals_list($country, 'terminal');
        if ( empty($terminals) || ! is_array($terminals) ) {
            $terminals = array();
        }
        $prepared_terminals = array();
        foreach ( $terminals as $group => $group_values ) {
            if ( ! is_array($group_values) ) {
                $prepared_terminals[] = array('label' => $group_values, 'value' => $group);
                continue;
            }
            foreach ( $group_values as $terminal_key => $terminal_name ) {
                $prepared_terminals[] = array('label' => $terminal_name, 'value' => $terminal_key);
            }
        }

        return array(
            'terminals' => $prepared_terminals,
        );
    }

    public function register_block_frontend_scripts() {
        $scripts = array(
            'omnivalt-block-frontend' => array(
                'js' => 'terminal-selection-block/frontend.js',
                'asset' => 'terminal-selection-block/frontend.asset.php',
            ),
        );

        $this->register_scripts($scripts);
    }

    public function register_block_editor_scripts() {
        $scripts = array(
            'omnivalt-block-editor' => array(
                'js' => 'terminal-selection-block/index.js',
                'asset' => 'terminal-selection-block/index.asset.php',
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

    public function load_update_callbacks() //TODO: Jei nereiks, istrinti
    {
        //https://github.com/woocommerce/woocommerce-blocks/blob/03c7cfacb225be345ab30240ef483476c080bae8/docs/third-party-developers/extensibility/rest-api/extend-rest-api-update-cart.md //TODO: Laikinai
        woocommerce_store_api_register_update_callback(array(
            'namespace' => 'omnivalt-update-terminals',
            'callback'  => array($this, 'update_callback_update_terminals')
        ));
    }

    public function update_callback_update_terminals( $data ) //TODO: Testi arba jei nereiks, istrinti
    {
        \culog($data, 'OmniCheck3');
        /* in JS:
        const { extensionCartUpdate } = window.wc.blocksCheckout;

        const buttonClickHandler = () => {
            extensionCartUpdate( {
                namespace: 'super-coupons',
                data: {
                    pointsInputValue,
                },
            } );
        };
        */
    }

    public function register_additional_actions()
    {
        add_action('wp_ajax_omnivalt_get_terminals', array($this, 'get_terminals_callback'));
        add_action('wp_ajax_nopriv_omnivalt_get_terminals', array($this, 'get_terminals_callback'));
    }

    public function get_terminals_callback() //TODO: Testi
    {
        if (isset($_GET['country'])) {
            $country = esc_attr($_GET['country']);
            /*$customer = \WC()->session->get('customer');
            $country2 = (!empty($customer['shipping_country'])) ? $customer['shipping_country'] : ((!empty($customer['country'])) ? $customer['country'] : 'LT');
            $terminals['salis2'] = $country2;*/
            \culog($country,'T1');
            $terminals = \OmnivaLt_Terminals::get_terminals_list($country, 'terminal');
            if ( empty($terminals) || ! is_array($terminals) ) {
                $terminals = array();
            }
            $prepared_terminals = array(
                array('label' => __('Select parcel terminal', 'omnivalt'), 'value' => '')
            );
            $this->build_terminals_list($terminals, $prepared_terminals);
            /*foreach ( $terminals as $group => $group_values ) {
                \culog($group_values,'T5');
                if ( ! is_array($group_values) ) {
                    $prepared_terminals[] = array('label' => $group_values, 'value' => $group);
                    continue;
                }
                //\culog($group_values,'T2');
                foreach ( $group_values as $terminal_key => $terminal_name ) {
                    //\culog($terminal_name,'T3');
                    $prepared_terminals[] = array('label' => $terminal_name, 'value' => $terminal_key);
                }
            }*/

            wp_send_json_success($prepared_terminals);
        } else {
            wp_send_json_error('Missing country parameter');
        }
    }

    private function build_terminals_list( $terminals_group, &$prepared_list )
    {
        foreach ( $terminals_group as $group => $group_values ) {
            if ( ! is_array($group_values) ) {
                $prepared_list[] = array('label' => $group_values, 'value' => $group);
                continue;
            }
            $this->build_terminals_list($group_values, $prepared_list);
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
