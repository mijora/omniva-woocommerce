<?php
class OmnivaLt_Manifest
{
  public static function load_admin_scripts()
  {
    $folder_css = '/assets/css/';
    $folder_js = '/assets/js/';

    wp_enqueue_style('omnivalt_admin_manifest', plugins_url($folder_css . 'omniva_admin_manifest.css', OmnivaLt_Core::$main_file_path, array(), OMNIVALT_VERSION));
    wp_enqueue_style('bootstrap-datetimepicker', plugins_url($folder_js . 'datetimepicker/bootstrap-datetimepicker.min.css', OmnivaLt_Core::$main_file_path));

    wp_enqueue_script('moment', plugins_url($folder_js . 'moment.min.js', OmnivaLt_Core::$main_file_path), array(), null, true);
    wp_enqueue_script('bootstrap-datetimepicker', plugins_url($folder_js . 'datetimepicker/bootstrap-datetimepicker.min.js', OmnivaLt_Core::$main_file_path), array('jquery', 'moment'), null, true);
    wp_enqueue_script('omniva_helper', plugins_url($folder_js . 'omniva_helper.js', OmnivaLt_Core::$main_file_path), array(), null, true);
    wp_enqueue_script('omniva_manifest', plugins_url($folder_js . 'omniva_manifest.js', OmnivaLt_Core::$main_file_path), array(), null, true);

    wp_localize_script('omniva_manifest', 'omnivaglobals', array(
      'cookie_checked_list' => 'omniva_checked',
    ));

    wp_localize_script('omniva_manifest', 'omnivatext', array(
      'alert_select_orders' => __('Please select orders', 'omnivalt'),
    ));
  }

  public static function register_menu_pages()
  {
    add_submenu_page(
      'woocommerce',
      __('Omniva shipping', 'omnivalt'),
      __('Omniva shipping', 'omnivalt'),
      'manage_woocommerce',
      'omniva-manifest',
      'OmnivaLt_Manifest::manifest_page',
      10
    );
  }

  public static function manifest_page()
  {
    include_once(OMNIVALT_DIR . 'manifest_page.php');
  }

  /**
   * Handle a custom query variable to get orders.
   * @param array $query - Args for WP_Query.
   * @param array $query_vars - Query vars from WC_Order_Query.
   * @return array modified $query
   */
  public static function handle_custom_query_var( $query, $query_vars ) {
    $configs_meta = OmnivaLt_Core::get_configs('meta_keys');
    if ( ! empty( $query_vars['omnivalt_method'] ) ) {
      $query['meta_query'][] = array(
        'key' => $configs_meta['method'],
        'value' => $query_vars['omnivalt_method']//esc_attr( $query_vars['omnivalt_method'] ),
      );
    }

    if ( isset( $query_vars['omnivalt_barcode'] ) ) {
      $query['meta_query'][] = array(
          'key' => $configs_meta['barcode'],
          'value' => $query_vars['omnivalt_barcode'],
          'compare' => 'LIKE'
      );
    }

    if ( isset( $query_vars['omnivalt_customer'] ) ) {
      $query['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => '_billing_first_name',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          ),
          array(
            'key' => '_billing_last_name',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          ),
          array(
            'key' => '_billing_company',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          ),
          array(
            'key' => '_shipping_first_name',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          ),
          array(
            'key' => '_shipping_last_name',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          ),
          array(
            'key' => '_shipping_company',
            'value' => $query_vars['omnivalt_customer'],
            'compare' => 'LIKE'
          ),
      );
    }

    if ( isset( $query_vars['omnivalt_manifest'] ) ) {
      $query['meta_query'][] = array(
        'key' => $configs_meta['manifest_date'],
        'compare' => ($query_vars['omnivalt_manifest'] ? 'EXISTS' : 'NOT EXISTS'),
      );
    }

    if ( isset( $query_vars['omnivalt_manifest_date'] ) ) {
      $filter_by_date = false;
      if ($query_vars['omnivalt_manifest_date'][0] && $query_vars['omnivalt_manifest_date'][1]) {
        $filter_by_date = array(
          'key' => $configs_meta['manifest_date'],
          'value' => $query_vars['omnivalt_manifest_date'],
          'compare' => 'BETWEEN'
        );
      } elseif ($query_vars['omnivalt_manifest_date'][0] && !$query_vars['omnivalt_manifest_date'][1]) {
        $filter_by_date = array(
          'key' => $configs_meta['manifest_date'],
          'value' => $query_vars['omnivalt_manifest_date'][0],
          'compare' => '>='
        );
      } elseif (!$query_vars['omnivalt_manifest_date'][0] && $query_vars['omnivalt_manifest_date'][1]) {
        $filter_by_date = array(
          'key' => $configs_meta['manifest_date'],
          'value' => $query_vars['omnivalt_manifest_date'][1],
          'compare' => '<='
        );
      }

      if ($filter_by_date) {
        $query['meta_query'][] = $filter_by_date;
      }
    }

    return $query;
  }

  public static function page_params()
  {
    return array(
      'strings' => array(
        'all_orders' => __('All orders', 'omnivalt'),
        'new_orders' => __('New orders', 'omnivalt'),
        'completed_orders' => __('Completed orders', 'omnivalt'),
      ),
      'filter_keys' => array('customer', 'status', 'barcode', 'id', 'start_date', 'end_date'),
      'per_page' => 25,
    );
  }

  public static function page_make_link($args)
  {
    $query_args = array('page' => 'omniva-manifest');
    $query_args = array_merge($query_args, $args);
    return add_query_arg($query_args, admin_url('/admin.php'));
  }

  public static function page_get_orders()
  {
    $configs = OmnivaLt_Core::get_configs();
    $page_params = self::page_params();

    $paged = (isset($_GET['paged'])) ? filter_input(INPUT_GET, 'paged') : 1;
    $action = (isset($_GET['action'])) ? filter_input(INPUT_GET, 'action') : 'all_orders';

    $filters = array();
    foreach ( $page_params['filter_keys'] as $filter_key ) {
      if (isset($_POST['filter_' . $filter_key]) && intval($_POST['filter_' . $filter_key]) !== -1) {
        $filters[$filter_key] = filter_input(INPUT_POST, 'filter_' . $filter_key);
      } else {
        $filters[$filter_key] = false;
      }
    }

    // Handle query variables depending on selected tab
    switch ( $action ) {
      case 'new_orders':
        $page_title = $page_params['strings'][$action];
        $args = array(
          'omnivalt_manifest' => false,
        );
        break;
      case 'completed_orders':
        $page_title = $page_params['strings'][$action];
        $args = array(
          'omnivalt_manifest' => true,
          // Latest manifest at the top
          'meta_query' => array(
            'relation' => 'OR',
            array(
              'key' => $configs['meta_keys']['manifest_date'],
            ),
            array( // Compatible for older
              'key' => $configs['meta_keys']['manifest_date_old'],
            ),
          ),
          'orderby' => 'meta_value',
          'order' => 'DESC'
        );
        break;
      case 'all_orders':
      default:
        $action = 'all_orders';
        $page_title = $page_params['strings']['all_orders'];
        $args = array();
        break;
    }

    foreach ( $filters as $key => $filter ) {
      if ( $filter ) {
        switch ($key) {
          case 'status':
            $args = array_merge(
              $args,
              array('status' => $filter)
            );
            break;
          case 'barcode':
            $args = array_merge(
              $args,
              array('omnivalt_barcode' => $filter)
            );
            break;
          case 'customer':
            $args = array_merge(
              $args,
              array('omnivalt_customer' => $filter)
            );
            break;
        }
      }
    }
    // Date filter is a special case
    if ( $filters['start_date'] || $filters['end_date'] ) {
      $args = array_merge(
        $args,
        array('omnivalt_manifest_date' => array($filters['start_date'], $filters['end_date']))
      );
    }

    // Get orders with extra info about the results.
    $shipping_methods = array('omnivalt');
    foreach ( $configs['method_params'] as $method ) {
      if ( ! $method['is_shipping_method'] ) continue;

      $shipping_methods[] = 'omnivalt_' . $method['key'];
    }
    $args = array_merge(
      $args,
      array(
        'omnivalt_method' => $shipping_methods,
        'paginate' => true,
        'limit' => $page_params['per_page'],
        'paged' => $paged,
      )
    );

    // Searching by ID takes priority
    $single_order = false;
    if ( $filters['id'] ) {
      $single_order = wc_get_order($filters['id']);
      if ( $single_order ) {
        $orders = array($single_order); // Table printer expects array
        $paged = 1;
      }
    }

    // If there is no search by ID use to custom query
    $results = false;
    if ( ! $single_order ) {
      $results = wc_get_orders($args);
      $orders = $results->orders;
    }

    $there_is_orders = ($single_order || ($results && $results->total > 0));

    // make pagination
    $page_links = false;
    if ($results) {
      $page_links = paginate_links(array(
        'base' => add_query_arg('paged', '%#%'),
        'format' => '?paged=%#%',
        'prev_text' => __('&laquo;', 'text-domain'),
        'next_text' => __('&raquo;', 'text-domain'),
        'total' => $results->max_num_pages,
        'current' => $paged,
        'type' => 'plain'
      ));
    }

    $order_statuses = wc_get_order_statuses();

    return array(
      'orders' => $orders,
      'statuses' => $order_statuses,
      'paged' => $paged,
      'action' => $action,
      'links' => $page_links,
      'is_orders' => $there_is_orders,
      'filters' => $filters,
    );
  }
}
