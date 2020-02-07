<?php
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * Manifest page defaults
 */
$tab_strings = array(
  'all_orders' => __('All orders', 'omnivalt'),
  'new_orders' => __('New orders', 'omnivalt'),
  'completed_orders' => __('Completed orders', 'omnivalt')
);

$filter_keys = array(
  'customer',
  'status',
  'barcode',
  'id',
  'start_date',
  'end_date'
);

// amount of orders to show per page
$max_per_page = 25;

/**
 * helper function to create links
 */
function make_link($args)
{
  $query_args = array('page' => 'omniva-manifest');
  $query_args = array_merge($query_args, $args);
  return add_query_arg($query_args, admin_url('/admin.php'));
}

// append custom css and js
do_action('omniva_admin_head');

// prep access to Omnivalt shipping class
$wc_shipping = new WC_Shipping();
$omnivalt = new Omnivalt_Shipping_Method();
?>

<div class="wrap">
  <h1><?php _e('Omniva manifest', 'omnivalt'); ?></h1>

  <?php

  $paged = 1;
  if (isset($_GET['paged']))
    $paged = filter_input(INPUT_GET, 'paged');

  $action = 'all_orders';
  if (isset($_GET['action'])) {
    $action = filter_input(INPUT_GET, 'action');
  }

  $filters = array();
  foreach ($filter_keys as $filter_key) {
    if (isset($_POST['filter_' . $filter_key]) && intval($_POST['filter_' . $filter_key]) !== -1) {
      $filters[$filter_key] = filter_input(INPUT_POST, 'filter_' . $filter_key); //$_POST['filter_' . $filter_key];
    } else {
      $filters[$filter_key] = false;
    }
  }

  // Handle query variables depending on selected tab
  switch ($action) {
    case 'new_orders':
      $page_title = $tab_strings[$action];
      $args = array(
        'omnivalt_manifest' => false,
      );
      break;
    case 'completed_orders':
      $page_title = $tab_strings[$action];
      $args = array(
        'omnivalt_manifest' => true,
        // latest manifest at the top
        'meta_key' => '_manifest_generation_date',
        'orderby' => 'meta_value',
        'order' => 'DESC'
      );
      break;
    case 'all_orders':
    default:
      $action = 'all_orders';
      $page_title = $tab_strings['all_orders'];
      $args = array();
      break;
  }

  foreach ($filters as $key => $filter) {
    if ($filter) {
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
  // date filter is a special case
  if ($filters['start_date'] || $filters['end_date']) {
    $args = array_merge(
      $args,
      array('omnivalt_manifest_date' => array($filters['start_date'], $filters['end_date']))
    );
  }

  // Get orders with extra info about the results.
  $args = array_merge(
    $args,
    array(
      'omnivalt_method' => ['omnivalt_pt', 'omnivalt_c', 'omnivalt'],
      'paginate' => true,
      'limit' => $max_per_page,
      'paged' => $paged,
    )
  );

  // Searching by ID takes priority
  $singleOrder = false;
  if ($filters['id']) {
    $singleOrder = wc_get_order($filters['id']);
    if ($singleOrder) {
      $orders = array($singleOrder); // table printer expects array
      $paged = 1;
    }
  }

  // if there is no search by ID use to custom query
  $results = false;
  if (!$singleOrder) {
    $results = wc_get_orders($args);
    $orders = $results->orders;
  }

  $thereIsOrders = ($singleOrder || ($results && $results->total > 0));

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
  ?>

      <div class="call-courier-container">
        <button id="omniva-call-btn" class="button action"><?php _e('Call Omniva courier', 'omnivalt') ?></button>
      </div>

      <ul class="nav nav-tabs">
        <?php foreach ($tab_strings as $tab => $tab_title) : ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $action == $tab ? 'active' : ''; ?>" href="<?php echo make_link(array('paged' => ($action == $tab ? $paged : 1), 'action' => $tab)); ?>"><?php echo $tab_title; ?></a>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($page_links) : ?>
        <div class="tablenav">
          <div class="tablenav-pages">
            <?php echo $page_links; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($thereIsOrders) : ?>
        <div class="mass-print-container">
          <form id="manifest-print-form" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_manifest" />
            <?php wp_nonce_field('omnivalt_manifest', 'omnivalt_manifest_nonce'); ?>
          </form>
          <form id="labels-print-form" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_labels" />
            <?php wp_nonce_field('omnivalt_labels', 'omnivalt_labels_nonce'); ?>
          </form>
          <button id="submit_manifest_items" title="<?php echo __('Generate manifest', 'omnivalt'); ?>" type="button" class="button action">
            <?php echo __('Generate manifest', 'omnivalt'); ?>
          </button>
          <button id="submit_manifest_labels" title="<?php echo __('Print labels', 'omnivalt'); ?>" type="button" class="button action">
            <?php echo __('Print labels', 'omnivalt'); ?>
          </button>
        </div>
      <?php endif; ?>

      <div class="table-container">
        <form id="filter-form" class="" action="<?php echo make_link(array('action' => $action)); ?>" method="POST">
          <?php wp_nonce_field('omnivalt_labels', 'omnivalt_labels_nonce'); ?>
          <table class="wp-list-table widefat fixed striped posts">
            <thead>

              <tr class="omniva-filter">
                <td class="manage-column column-cb check-column"><input type="checkbox" class="check-all" /></td>
                <th class="manage-column">
                  <input type="text" class="d-inline" name="filter_id" id="filter_id" value="<?php echo $filters['id']; ?>" placeholder="<?php echo __('ID', 'omnivalt'); ?>" aria-label="Order ID filter">
                </th>
                <th class="manage-column">
                  <input type="text" class="d-inline" name="filter_customer" id="filter_customer" value="<?php echo $filters['customer']; ?>" placeholder="<?php echo __('Customer', 'omnivalt'); ?>" aria-label="Order ID filter">
                </th>
                <th class="manage-column">
                  <select class="d-inline" name="filter_status" id="filter_status" aria-label="Order status filter">
                    <option value="-1" selected>All</option>
                    <?php foreach ($order_statuses as $status_key => $status) : ?>
                      <option value="<?php echo $status_key; ?>" <?php echo ($status_key == $filters['status'] ? 'selected' : ''); ?>><?php echo $status; ?></option>
                    <?php endforeach; ?>
                  </select>
                </th>
                <th class="manage-column">
                </th>
                <th class="manage-column">
                  <input type="text" class="d-inline" name="filter_barcode" id="filter_barcode" value="<?php echo $filters['barcode']; ?>" placeholder="<?php echo __('Barcode', 'omnivalt'); ?>" aria-label="Order barcode filter">
                </th>
                <th class="manage-column">
                  <div class='datetimepicker'>
                    <div>
                      <input name="filter_start_date" type='text' class="" id='datetimepicker1' data-date-format="YYYY-MM-DD" value="<?php echo $filters['start_date']; ?>" placeholder="<?php echo __('From', 'omnivalt'); ?>" autocomplete="off" />
                    </div>
                    <div>
                      <input name="filter_end_date" type='text' class="" id='datetimepicker2' data-date-format="YYYY-MM-DD" value="<?php echo $filters['end_date']; ?>" placeholder="<?php echo __('To', 'omnivalt'); ?>" autocomplete="off" />
                    </div>
                  </div>
                </th>
                <th class="manage-column">
                  <div class="omniva-action-buttons-container">
                    <button class="button action" type="submit"><?php echo __('Filter', 'omnivalt'); ?></button>
                    <button id="clear_filter_btn" class="button action" type="submit"><?php echo __('Reset', 'omnivalt'); ?></button>
                  </div>
                </th>
              </tr>

              <tr class="table-header">
                <td class="manage-column column-cb check-column"></td>
                <th scope="col" class="manage-column"><?php echo __('ID', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Customer', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Order Status', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Service', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Barcode', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Manifest date', 'omnivalt'); ?></th>
                <th scope="col"></th>
              </tr>

            </thead>
            <tbody>
              <?php $$date_tracker = false; ?>
              <?php foreach ($orders as $order) : ?>
                <?php
                  $manifest_date = $order->get_meta('_manifest_generation_date');
                  $date = date('Y-m-d H:i', strtotime($manifest_date));
                  ?>
                <?php if ($action == 'completed_orders' && $date_tracker !== $date) : ?>
                  <tr>
                    <td colspan="8" class="manifest-date-title">
                      <?php echo $date_tracker = $date; ?>
                    </td>
                  </tr>
                <?php endif; ?>
                <tr class="data-row">
                  <th scope="row" class="check-column"><input type="checkbox" name="items[]" class="manifest-item" value="<?php echo $order->get_id(); ?>" /></th>
                  <td class="manage-column">
                    <a href="<?php echo $order->get_edit_order_url(); ?>">#<?php echo $order->get_order_number(); ?></a>
                  </td>
                  <td class="column-order_number">
                    <div class="data-grid-cell-content">
                      <?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?>
                    </div>
                  </td>
                  <td class="column-order_status">
                    <div class="data-grid-cell-content">
                      <?php echo wc_get_order_status_name($order->get_status()); ?>
                    </div>
                  </td>
                  <td class="manage-column">
                    <div class="data-grid-cell-content">
                      <?php do_action('woocommerce_admin_order_data_after_shipping_address', $order, false); ?>
                    </div>
                  </td>
                  <td class="manage-column">
                    <div class="data-grid-cell-content">
                      <?php $barcode = $order->get_meta('_omnivalt_barcode'); ?>
                      <?php if ($barcode) : ?>
                        <?php do_action('print_omniva_tracking_url', $omnivalt->settings['shop_countrycode'], $barcode); ?>
                        <?php $error = $order->get_meta('_omnivalt_error'); ?>
                        <?php if ($error) : ?>
                          <br />Error: <?php echo $error; ?>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="manage-column">
                    <div class="data-grid-cell-content">
                      <?php echo $manifest_date; ?>
                    </div>
                  </td>
                  <td class="manage-column">
                    <a href="admin-post.php?action=omnivalt_labels&post=<?php echo $order->get_id(); ?>" class="button action">
                      <?php echo __('Print labels', 'omnivalt'); ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$orders) : ?>
                <tr>
                  <td colspan="8">
                    <?php echo __('No orders found', 'woocommerce'); ?>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </form>
      </div>

      <!-- Modal Carier call-->
      <div id="omniva-courier-modal" class="modal" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
          <div class="alert-info">
            <p><span><?php _e('Important!', 'omnivalt') ?></span> <?php _e('Latest call for same day pickup is until 3 pm.', 'omnivalt') ?></p>
            <p><?php _e('Address and contact information can be changed in Omniva settings.', 'omnivalt') ?></p>
          </div>
          <form id="omniva-call" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_call_courier" />
            <?php wp_nonce_field('omnivalt_call_courier', 'omnivalt_call_courier_nonce'); ?>
            <div><span><?php echo __("Shop name", 'omnivalt'); ?>:</span> <?php echo $omnivalt->settings['shop_name']; ?></div>
            <div><span><?php echo __("Shop phone number", 'omnivalt'); ?>:</span> <?php echo $omnivalt->settings['shop_phone']; ?></div>
            <div><span><?php echo __("Shop postcode", 'omnivalt'); ?>:</span> <?php echo $omnivalt->settings['shop_postcode']; ?></div>
            <div>
              <span><?php echo __("Shop address", 'omnivalt'); ?>:</span> <?php echo $omnivalt->settings['shop_address'] . ', ' . $omnivalt->settings['shop_city']; ?>
            </div>
            <div class="modal-footer">
              <button type="submit" id="omniva-call-btn" class="button action"><?php _e('Call Omniva courier', 'omnivalt') ?></button>
              <button type="button" id="omniva-call-cancel-btn" class="button action"><?php _e('Cancel') ?></button>
            </div>
          </form>
        </div>
      </div>
      <!--/ Modal Carier call-->

      <script>
        jQuery('document').ready(function($) {
          // "From" date picker
          $('#datetimepicker1').datetimepicker({
            pickTime: false,
            useCurrent: false
          });
          // "To" date picker
          $('#datetimepicker2').datetimepicker({
            pickTime: false,
            useCurrent: false
          });

          // Set limits depending on date picker selections
          $("#datetimepicker1").on("dp.change", function(e) {
            $('#datetimepicker2').data("DateTimePicker").setMinDate(e.date);
          });
          $("#datetimepicker2").on("dp.change", function(e) {
            $('#datetimepicker1').data("DateTimePicker").setMaxDate(e.date);
          });

          // Pass on filters to pagination links
          $('.tablenav-pages').on('click', 'a', function(e) {
            e.preventDefault();
            var form = document.getElementById('filter-form');
            form.action = e.target.href;
            form.submit();
          });

          // Filter cleanup and page reload
          $('#clear_filter_btn').on('click', function(e) {
            e.preventDefault();
            $('#filter_id, #filter_customer, #filter_barcode, #datetimepicker1, #datetimepicker2').val('');
            $('#filter_status').val('-1');
            document.getElementById('filter-form').submit();
          });

          $('#omniva-courier-modal').on('click', function(e) {
            if (e.target === this) {
              $('#omniva-courier-modal').removeClass('open');
            }
          });

          $('#omniva-call-btn').on('click', function(e) {
            e.preventDefault();
            $('#omniva-courier-modal').addClass('open');
          });

          $('#omniva-call-cancel-btn').on('click', function(e) {
            e.preventDefault();
            $('#omniva-courier-modal').removeClass('open');
          });

          $('#submit_manifest_items').on('click', function() {
            var ids = "";
            $('#manifest-print-form .post_id').remove();
            $('.manifest-item:checked').each(function() {
              ids += $(this).val() + ";";
              var id = $(this).val();
              $('#manifest-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
            });
            $('#item_ids').val(ids);
            if (ids == "") {
              alert('<?php echo __('Select orders', 'omnivalt'); ?>');
            } else {
              $('#manifest-print-form').submit();
            }

          });

          $('#submit_manifest_labels').on('click', function() {
            var ids = "";
            $('#labels-print-form .post_id').remove();
            $('.manifest-item:checked').each(function() {
              ids += $(this).val() + ";";
              var id = $(this).val();
              $('#labels-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
            });
            if (ids == "") {
              alert('<?php echo __('Select orders', 'omnivalt'); ?>');
            } else {
              $('#labels-print-form').submit();
            }
          });

          $('.check-all').on('click', function() {
            var checked = $(this).prop('checked');
            $(this).parents('table').find('.manifest-item').each(function() {
              $(this).prop('checked', checked);
            });
          });
        });
      </script>
