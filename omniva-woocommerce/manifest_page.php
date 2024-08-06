<?php
if ( ! defined('ABSPATH') ) {
  exit; // Exit if accessed directly
}

// Prepare
$shipping_settings = OmnivaLt_Core::get_settings();
$configs = OmnivaLt_Core::get_configs();
$page_params = OmnivaLt_Manifest::page_params();

$orders_data = OmnivaLt_Manifest::page_get_orders();
$selected_orders = array();
if ( isset($_COOKIE['omniva_checked']) ) {
  $cookie_value = json_decode(stripslashes($_COOKIE['omniva_checked']));
  if (is_array($cookie_value)) {
    $selected_orders = $cookie_value;
  }
}

$manifest_enabled = (!isset($shipping_settings['manifest_enable']) || $shipping_settings['manifest_enable'] === 'yes') ? true : false;
$active_omx = ($configs['api']['type'] === 'omx');
$current_courier_calls = OmnivaLt_Helper::get_courier_calls();


// Append custom css and js
do_action('omniva_admin_manifest_head');
?>

<div class="wrap page-omniva_manifest">
  <h1><?php _e('Omniva shipping', 'omnivalt'); ?></h1>

      <div class="call-courier-container">
        <button id="omniva-call-btn" class="button action">🚚 <?php _e('Call Omniva courier', 'omnivalt') ?></button>
        <?php if ( ! empty($current_courier_calls) ) : ?>
          <div class="current_calls">
            <table>
              <tr>
                <th colspan="2">
                  <?php echo __('Scheduled courier arrivals', 'omnivalt') . OmnivaLt_Helper::custom_tip(__('After arrival time expires, the record is automatically removed', 'omnivalt')); ?>
                </th>
              </tr>
              <?php foreach( $current_courier_calls as $call ) : ?>
                <?php
                $call_start_date = date('Y-m-d', strtotime($call['start']));
                $call_start_time = date('H:i', strtotime($call['start']));
                $call_end_date = date('Y-m-d', strtotime($call['end']));
                $call_end_time = date('H:i', strtotime($call['end']));
                $call_string = '<span class="date">' . $call_start_date . '</span> <span class="time">' . $call_start_time . '</span> - ';
                if ( strtotime($call_start_date) != strtotime($call_end_date) ) {
                  $call_string .= '<span class="date">' . $call_end_date . '</span> ';
                }
                $call_string .= '<span class="time">' . $call_end_time . '</span>';
                ?>
                <tr>
                  <td><?php echo $call_string; ?></td>
                  <td>
                    <input type="hidden" name="call_id" value="<?php echo esc_html($call['id']); ?>" />
                    <button class="icon-btn action-cancel" value="cancel" title="<?php _e('Cancel this call', 'omnivalt'); ?>"><span class="dashicons dashicons-no"></span></button>
                    <button class="icon-btn action-remove" value="remove" title="<?php _e('Courier arrived and this can be removed', 'omnivalt'); ?>"><span class="dashicons dashicons-minus"></span></button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <ul class="nav nav-tabs">
        <?php foreach ( $page_params['strings'] as $tab => $tab_title ) : ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $orders_data['action'] == $tab ? 'active' : ''; ?>" href="<?php echo OmnivaLt_Manifest::page_make_link(array('paged' => ($orders_data['action'] == $tab ? $orders_data['paged'] : 1), 'action' => $tab)); ?>"><?php echo $tab_title; ?></a>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ( $orders_data['links'] ) : ?>
        <div class="tablenav">
          <div class="tablenav-pages">
            <?php echo $orders_data['links']; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ( $orders_data['is_orders'] ) : ?>
        <div class="mass-print-container">
          <form id="manifest-print-form" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_manifest" />
            <?php wp_nonce_field('omnivalt_manifest', 'omnivalt_manifest_nonce'); ?>
          </form>
          <form id="labels-print-form" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_labels" />
            <?php wp_nonce_field('omnivalt_labels', 'omnivalt_labels_nonce'); ?>
          </form>
          <?php $desc = ''; ?>
          <div id="selected-orders" class="selected-orders <?php echo ($desc) ? 'has-desc' : ''; ?>" style="<?php echo (empty($selected_orders)) ? 'display:none' : ''; ?>">
            <span class="title"><?php echo __('Selected', 'omnivalt'); ?><?php echo ($desc) ? '*' : ''; ?>:</span>
            <?php foreach ($selected_orders as $order_id) : ?>
              <span class="item" data-id="<?php echo $order_id; ?>"><?php echo '#' . $order_id; ?><span class="dashicons dashicons-no"></span></span>
            <?php endforeach; ?>
            <?php if ($desc) : ?>
              <span class="desc">*<?php echo $desc; ?></span>
            <?php endif; ?>
          </div>
          <?php if ($manifest_enabled) : ?>
            <button id="submit_manifest_items_1" title="<?php echo __('Generate manifest', 'omnivalt'); ?>" type="button" class="button action">
              <?php echo __('Generate manifest', 'omnivalt'); ?>
            </button>
          <?php endif; ?>
          <button id="submit_manifest_labels_1" title="<?php echo __('Generate and print labels', 'omnivalt'); ?>" type="button" class="button action">
            <?php echo __('Generate and print labels', 'omnivalt'); ?>
          </button>
        </div>
      <?php endif; ?>

      <div class="table-container">
        <form id="filter-form" class="" action="<?php echo OmnivaLt_Manifest::page_make_link(array('action' => $orders_data['action'])); ?>" method="POST">
          <?php wp_nonce_field('omnivalt_labels', 'omnivalt_labels_nonce'); ?>
          <table class="wp-list-table widefat fixed striped posts">
            <thead>

              <tr class="omniva-filter">
                <td class="manage-column column-cb check-column"><input type="checkbox" class="check-all" /></td>
                <th class="manage-column column-order_id">
                  <input type="text" class="d-inline" name="filter_id" id="filter_id" value="<?php echo $orders_data['filters']['id']; ?>" placeholder="<?php echo __('ID', 'omnivalt'); ?>" aria-label="Order ID filter">
                </th>
                <th class="manage-column">
                  <input type="text" class="d-inline" name="filter_customer" id="filter_customer" value="<?php echo $orders_data['filters']['customer']; ?>" placeholder="<?php echo __('Customer', 'omnivalt'); ?>" aria-label="Order ID filter">
                </th>
                <th class="column-order_status">
                  <select class="d-inline" name="filter_status" id="filter_status" aria-label="Order status filter">
                    <option value="-1" selected><?php echo _x('All', 'All status', 'omnivalt'); ?></option>
                    <?php foreach ( $orders_data['statuses'] as $status_key => $status ) : ?>
                      <option value="<?php echo $status_key; ?>" <?php echo ($status_key == $orders_data['filters']['status'] ? 'selected' : ''); ?>><?php echo $status; ?></option>
                    <?php endforeach; ?>
                  </select>
                </th>
                <th class="column-order_info">
                </th>
                <th class="manage-column">
                </th>
                <th class="manage-column">
                  <input type="text" class="d-inline" name="filter_barcode" id="filter_barcode" value="<?php echo $orders_data['filters']['barcode']; ?>" placeholder="<?php echo __('Barcode', 'omnivalt'); ?>" aria-label="Order barcode filter">
                </th>
                <?php if ($manifest_enabled) : ?>
                  <th class="column-manifest_date">
                    <div class='datetimepicker'>
                      <div>
                        <input name="filter_start_date" type='text' class="" id='datetimepicker1' data-date-format="YYYY-MM-DD" value="<?php echo $orders_data['filters']['start_date']; ?>" placeholder="<?php echo __('From', 'omnivalt'); ?>" autocomplete="off" />
                      </div>
                      <div>
                        <input name="filter_end_date" type='text' class="" id='datetimepicker2' data-date-format="YYYY-MM-DD" value="<?php echo $orders_data['filters']['end_date']; ?>" placeholder="<?php echo __('To', 'omnivalt'); ?>" autocomplete="off" />
                      </div>
                    </div>
                  </th>
                <?php endif; ?>
                <th class="manage-column">
                  <div class="omniva-action-buttons-container">
                    <button class="button action" type="submit"><?php echo __('Filter', 'omnivalt'); ?></button>
                    <button id="clear_filter_btn" class="button action" type="submit"><?php echo __('Reset', 'omnivalt'); ?></button>
                  </div>
                </th>
              </tr>

              <tr class="table-header">
                <td class="manage-column column-cb check-column"></td>
                <th scope="col" class="column-order_id"><?php echo __('ID', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Customer', 'omnivalt'); ?></th>
                <th scope="col" class="column-order_status"><?php echo __('Order Status', 'omnivalt'); ?></th>
                <th scope="col" class="column-order_info"><?php echo __('Order information', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Service', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php echo __('Barcode', 'omnivalt'); ?></th>
                <?php if ($manifest_enabled) : ?>
                  <th scope="col" class="column-manifest_date"><?php echo __('Manifest date', 'omnivalt'); ?></th>
                <?php endif; ?>
                <th scope="col" class="manage-column"><?php echo __('Actions', 'omnivalt'); ?></th>
              </tr>

            </thead>
            <tbody>
              <?php $date_tracker = false; ?>
              <?php foreach ( $orders_data['orders'] as $order ) : ?>
                <?php
                $order_data = OmnivaLt_Wc_Order::get_data($order->get_id());
                $barcodes = $order_data->omniva->barcodes;
                $manifest_date = $order_data->omniva->manifest_date;
                $date = date('Y-m-d H:i', strtotime($manifest_date));
                $order_size = $order_data->shipment->size;
                $total_shipments = $order_data->shipment->total_shipments;
                ?>
                <?php if ( OmnivaLt_Manifest::is_mannifest_orders_table($orders_data['action']) && $date_tracker !== $date ) : ?>
                  <tr>
                    <?php $colspan = ($manifest_enabled) ? 9 : 8; ?>
                    <td colspan="<?php echo $colspan; ?>" class="manifest-date-title">
                      <?php echo $date_tracker = $manifest_date; ?>
                    </td>
                  </tr>
                <?php endif; ?>
                <tr class="data-row">
                  <?php $checked = (in_array($order_data->id, $selected_orders)) ? 'checked' : ''; ?>
                  <th scope="row" class="check-column"><input type="checkbox" name="items[]" class="manifest-item" value="<?php echo $order_data->id; ?>" <?php echo $checked; ?>/></th>
                  <td class="manage-column column-order_id">
                    <a href="<?php echo $order_data->admin->url_edit; ?>">#<?php echo $order_data->number; ?></a>
                  </td>
                  <td class="column-order_customer">
                    <div class="data-grid-cell-content">
                      <span class="customer-name"><?php echo OmnivaLt_Order::get_customer_fullname($order_data); ?></span>
                      <span class="customer-company"><?php echo OmnivaLt_Order::get_customer_company($order_data); ?></span>
                    </div>
                  </td>
                  <td class="column-order_status">
                    <div class="data-grid-cell-content">
                      <mark class="order-status status-<?php echo $order_data->status; ?>">
                        <span><?php echo wc_get_order_status_name($order_data->status); ?></span>
                      </mark>
                    </div>
                  </td>
                  <td class="column-order_info">
                    <div class="data-grid-cell-content">
                      <b><?php echo __('Date', 'omnivalt'); ?>:</b> <?php echo $order_data->created; ?>
                    </div>
                    <div class="data-grid-cell-content">
                      <b><?php echo __('Amount', 'omnivalt'); ?>:</b> <?php echo OmnivaLt_Order::get_price_text($order_data->payment->total); ?>
                    </div>
                    <div class="data-grid-cell-content">
                      <b><?php echo __('Weight', 'omnivalt'); ?>:</b> <?php echo OmnivaLt_Order::get_weight_text($order_size); ?>
                    </div>
                    <div class="data-grid-cell-content">
                      <b><?php echo __('Size', 'omnivalt'); ?>:</b> <?php echo OmnivaLt_Order::get_dimmension_text($order_size); ?>
                    </div>
                    <div class="data-grid-cell-content">
                      <b><?php echo __('Total shipments', 'omnivalt'); ?>:</b> <?php echo (! empty($total_shipments)) ? $total_shipments : 1; ?>
                    </div>
                  </td>
                  <td class="manage-column">
                    <div class="data-grid-cell-content">
                      <?php OmnivaLt_Order::admin_order_display($order_data->id, false); ?>
                    </div>
                  </td>
                  <td class="manage-column">
                    <div class="data-grid-cell-content">
                      <?php if ( ! empty($barcodes) ) : ?>
                        <?php foreach ( $barcodes as $barcode ) : ?>
                          <?php do_action('print_omniva_tracking_url', $barcode, $shipping_settings['shop_countrycode']); ?>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      <?php $error = $order_data->omniva->error; ?>
                      <?php if ( $error ) : ?>
                        <?php if ( ! empty($barcodes) ) : ?><br /><?php endif; ?>
                        <span><?php echo '<b>' . __('Error', 'omnivalt') . ':</b> ' . $error; ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <?php if ($manifest_enabled) : ?>
                    <td class="column-manifest_date">
                      <div class="data-grid-cell-content">
                        <?php echo $manifest_date; ?>
                      </div>
                    </td>
                  <?php endif; ?>
                  <td class="manage-column">
                    <a href="admin-post.php?action=omnivalt_labels&post=<?php echo $order_data->id; ?>" class="button action">
                      <?php
                      if ( ! empty($barcodes) ) {
                        echo _x('Print', 'button', 'omnivalt');
                      } else {
                        echo _x('Generate', 'button', 'omnivalt');
                      }
                      ?>
                    </a>
                    <?php if ( ! empty($barcodes) ) : ?>
                      <a href="admin-post.php?action=omnivalt_labels&post=<?php echo $order_data->id; ?>&process=regenerate" class="button action">
                        <?php echo _x('Regenerate', 'button', 'omnivalt'); ?>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if ( ! $orders_data['orders'] ) : ?>
                <tr>
                  <td colspan="9">
                    <?php echo __('No orders found', 'woocommerce'); ?>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </form>
      </div>

      <?php if ( $orders_data['is_orders'] ) : ?>
        <div class="mass-print-container">
          <?php if ($manifest_enabled) : ?>
            <button id="submit_manifest_items_2" title="<?php echo __('Generate manifest', 'omnivalt'); ?>" type="button" class="button action">
              <?php echo __('Generate manifest', 'omnivalt'); ?>
            </button>
          <?php endif; ?>
          <button id="submit_manifest_labels_2" title="<?php echo __('Generate and print labels', 'omnivalt'); ?>" type="button" class="button action">
            <?php echo __('Generate and print labels', 'omnivalt'); ?>
          </button>
        </div>
      <?php endif; ?>

      <!-- Modal Courier call-->
      <div id="omniva-courier-modal" class="modal" role="dialog">
        <!-- Modal content: Call-->
        <div id="modal-content-call" class="modal-content">
          <div class="alert-info">
            <p><span><?php _e('Important!', 'omnivalt') ?></span> <?php _e('Latest call for same day pickup is until 3 pm.', 'omnivalt') ?></p>
            <p><?php _e('Address and contact information can be changed in Omniva settings.', 'omnivalt') ?></p>
          </div>
          <form id="omniva-call" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_call_courier" />
            <?php wp_nonce_field('omnivalt_call_courier', 'omnivalt_call_courier_nonce'); ?>
            <div><span><?php _e("Shop name", 'omnivalt'); ?>:</span> <?php echo $shipping_settings['shop_name']; ?></div>
            <div><span><?php _e("Shop phone number", 'omnivalt'); ?>:</span> <?php echo (empty($shipping_settings['shop_mobile'])) ? $shipping_settings['shop_phone'] : $shipping_settings['shop_mobile']; ?></div>
            <div><span><?php _e("Shop postcode", 'omnivalt'); ?>:</span> <?php echo $shipping_settings['shop_postcode']; ?></div>
            <div>
              <span><?php _e("Shop address", 'omnivalt'); ?>:</span> <?php echo $shipping_settings['shop_address'] . ', ' . $shipping_settings['shop_city']; ?>
            </div>
            <div><span><?php _e("Comment", 'omnivalt'); ?>:</span> <?php echo (! empty($shipping_settings['pickup_comment'])) ? $shipping_settings['pickup_comment'] : '-'; ?></div>
            <table cellspacing="0">
              <tr>
                <th>
                  <label for="call_quantity"><?php _e("Number of parcels", 'omnivalt'); ?>:</label>
                </th>
                <td>
                  <input type="number" id="call_quantity" name="call_quantity" min="0" max="29" step="1" value="<?php echo count($selected_orders); ?>"/>
                </td>
              </tr>
              <tr title="<?php echo ($active_omx) ? '' : __('This feature is not available', 'omnivalt'); ?>">
                <th>
                  <label for="call_checkboxes_heavy"><?php _e("Shipments is heavy", 'omnivalt'); ?>:</label>
                </th>
                <td>
                  <label>
                    <input type="checkbox" id="call_checkboxes_heavy" name="call_checkboxes[]" value="heavy" <?php echo ($active_omx) ? '' : 'disabled'; ?>/>
                    <?php _e("Shipments weight exceeds 30 kg", 'omnivalt'); ?>
                  </label>
                </td>
              </tr>
              <tr title="<?php echo ($active_omx) ? '' : __('This feature is not available', 'omnivalt'); ?>">
                <th>
                  <label for="call_checkboxes_twoman"><?php _e("Need two man", 'omnivalt'); ?>:</label>
                </th>
                <td>
                  <label>
                    <input type="checkbox" id="call_checkboxes_twoman" name="call_checkboxes[]" value="twoman" <?php echo ($active_omx) ? '' : 'disabled'; ?>/>
                    <?php _e("2 people are needed to pick up the shipments", 'omnivalt'); ?>
                  </label>
                </td>
              </tr>
            </table>
            <div class="modal-footer">
              <button type="submit" id="omniva-call-confirm-btn" class="button action"><?php _e('Call Omniva courier', 'omnivalt') ?></button>
              <button type="button" id="omniva-call-cancel-btn" class="button action"><?php _e('Cancel') ?></button>
            </div>
          </form>
        </div>
        <!-- Modal content: Cancel-->
        <div id="modal-content-cancel" class="modal-content">
          <form id="omniva-cancel" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_cancel_courier" />
            <?php wp_nonce_field('omnivalt_cancel_courier', 'omnivalt_cancel_courier_nonce'); ?>
            <input id="omniva-cancel-id" type="hidden" name="call_id" value="" />
            <div><span><?php _e('Are you sure you want to cancel the courier arrival?', 'omnivalt'); ?></span></div>
            <div class="modal-footer">
              <button type="submit" id="omniva-cancel-confirm-btn" class="button action"><?php _e('Cancel Omniva courier', 'omnivalt') ?></button>
              <button type="button" id="omniva-call-cancel-btn" class="button action"><?php _e('No', 'omnivalt') ?></button>
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
        });
      </script>
</div>
