<?php
class OmnivaLt_Manifest
{
  public static function load_admin_scripts()
  {
    $folder_css = '/assets/css/';
    $folder_js = '/assets/js/';

    wp_enqueue_style('omnivalt_admin_woo', plugins_url($folder_css . 'omniva_admin_woo.css', OmnivaLt_Core::$main_file_path, array(), OMNIVALT_VERSION));
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
        'registered_orders' => __('Registered orders', 'omnivalt'),
        'manifest_orders' => __('Orders ready to ship', 'omnivalt'),
        'completed_orders' => __('Completed orders', 'omnivalt'),
      ),
      'filter_keys' => array('customer', 'status', 'barcode', 'id', 'start_date', 'end_date'),
      'per_page' => 25,
    );
  }

  public static function is_mannifest_orders_table( $tab_key )
  {
    $manifest_table_tabs = array('manifest_orders', 'completed_orders');
    return (in_array($tab_key, $manifest_table_tabs));
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

    $per_page = (isset($_GET['perpage'])) ? filter_input(INPUT_GET, 'perpage') : $page_params['per_page'];
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

    $shipping_methods = array('omnivalt');
    foreach ( $configs['method_params'] as $method ) {
      if ( ! $method['is_shipping_method'] ) continue;

      $shipping_methods[] = 'omnivalt_' . $method['key'];
    }

    $args = array(
      'paginate' => true,
      'limit' => $per_page,
      'paged' => $paged,
      'omnivalt_method' => $shipping_methods, // Compatible with old
      'meta_query' => array(
        'relation' => 'AND',
        array(
          'key' => '_omnivalt_method',
          'value' => $shipping_methods,
          'compare' => 'IN',
        ),
      ),
    );

    // Handle query variables depending on selected tab
    switch ( $action ) {
      case 'new_orders':
        $page_title = $page_params['strings'][$action];
        $args['status'] = array('wc-processing', 'wc-on-hold', 'wc-pending');
        $args[$configs['meta_keys']['manifest_date']] = false; // Compatible with old
        $args['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => $configs['meta_keys']['manifest_date'],
            'compare' => 'NOT EXISTS',
          ),
          array(
            'key' => $configs['meta_keys']['manifest_date'],
            'compare' => '=',
            'value' => '',
          ),
        );
        $args['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => $configs['meta_keys']['barcodes'],
            'compare' => 'NOT EXISTS',
          ),
          array(
            'key' => $configs['meta_keys']['barcodes'],
            'compare' => '=',
            'value' => '',
          ),
        );
        break;
      case 'registered_orders':
        $page_title = $page_params['strings'][$action];
        $args['status'] = array('wc-processing', 'wc-on-hold', 'wc-pending');
        $args[$configs['meta_keys']['manifest_date']] = false; // Compatible with old
        $args['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => $configs['meta_keys']['manifest_date'],
            'compare' => 'NOT EXISTS',
          ),
          array(
            'key' => $configs['meta_keys']['manifest_date'],
            'compare' => '=',
            'value' => '',
          ),
        );
        $args['meta_query'][] = array(
          'key' => $configs['meta_keys']['barcodes'],
          'compare' => 'EXISTS',
        );
        $args['meta_query'][] = array(
          'key' => $configs['meta_keys']['barcodes'],
          'compare' => '!=',
          'value' => '',
        );
        break;
      case 'manifest_orders':
        $page_title = $page_params['strings'][$action];
        $args['status'] = array('wc-processing', 'wc-on-hold', 'wc-pending');
        $args[$configs['meta_keys']['manifest_date']] = true; // Compatible with old
        $args['meta_query'][] = array(
          'key' => $configs['meta_keys']['manifest_date'],
          'compare' => 'EXISTS',
        );
        $args['meta_query'][] =  array(
          'key' => $configs['meta_keys']['manifest_date'],
          'compare' => '!=',
          'value' => '',
        );
        $args['orderby'] = 'meta_value';
        $args['order'] = 'DESC';
        break;
      case 'completed_orders':
        $page_title = $page_params['strings'][$action];
        $args['status'] = array('wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed');
        $args[$configs['meta_keys']['manifest_date']] = true; // Compatible with old
        $args['meta_query'][] = array(
          'key' => $configs['meta_keys']['manifest_date'],
          'compare' => 'EXISTS',
        );
        $args['meta_query'][] =  array(
          'key' => $configs['meta_keys']['manifest_date'],
          'compare' => '!=',
          'value' => '',
        );
        $args['orderby'] = 'meta_value';
        $args['order'] = 'DESC';
        break;
      case 'all_orders':
      default:
        $action = 'all_orders';
        $page_title = $page_params['strings']['all_orders'];
        break;
    }

    foreach ( $filters as $key => $filter ) {
      if ( $filter ) {
        switch ($key) {
          case 'status':
            $args['status'] = $filter;
            break;
          case 'barcode':
            $args['meta_query'][] = array(
              'key' => $configs['meta_keys']['barcodes'],
              'value' => $filter,
              'compare' => 'LIKE',
            );
            break;
          case 'customer':
            $args['field_query'][] = array(
              'relation' => 'OR',
              array(
                'field' => 'billing_first_name',
                'value' => $filter,
                'compare' => 'LIKE'
              ),
              array(
                'field' => 'billing_last_name',
                'value' => $filter,
                'compare' => 'LIKE'
              ),
            );
            $args['omnivalt_customer'] = $filter; // Compatible with old
            break;
        }
      }
    }
    // Date filter is a special case
    if ( $filters['start_date'] || $filters['end_date'] ) {
      $args[$configs['meta_keys']['manifest_date']] = array($filters['start_date'], $filters['end_date']); // Compatible with old
      $args['meta_query'][] = array(
        'key' => $configs['meta_keys']['manifest_date'],
        'value' => array($filters['start_date'], $filters['end_date']),
        'compare' => 'BETWEEN',
      );
    }

    // Searching by ID takes priority
    $single_order = false;
    if ( $filters['id'] ) {
      $single_order = OmnivaLt_Wc_Order::get_order($filters['id']);
      if ( $single_order ) {
        $orders = array($single_order); // Table printer expects array
        $paged = 1;
      }
    }

    // If there is no search by ID use to custom query
    $results = false;
    if ( ! $single_order ) {
      $results = OmnivaLt_Wc_Order::get_orders($args, true);
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

    $order_statuses = OmnivaLt_Wc_Order::get_all_statuses();

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
