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

$is_wrong_timezone = (OmnivaLt_Helper::get_timezone_offset(OmnivaLt_Helper::get_local_timezone_string()) !== OmnivaLt_Helper::get_timezone_offset('Europe/Tallinn'));
$timezone_alert = __('The offset of the timezone of your website is different from the offset of the timezone of the Omniva server, so the courier call time is displayed differently than specified in the settings', 'omnivalt');

// Append custom css and js
do_action('omniva_admin_manifest_head');
?>

<div class="wrap page-omniva_manifest omnivalt-manifest-page">
  <div id="omnivalt-manifest-root" class="omnivalt-manifest-page__root">
    <header class="omnivalt-manifest-page__header">
      <div>
        <div class="omnivalt-manifest-page__breadcrumb">
          <span><?php esc_html_e('WooCommerce', 'omnivalt'); ?></span>
          <span aria-hidden="true">/</span>
          <span><?php esc_html_e('Omniva', 'omnivalt'); ?></span>
        </div>
        <h1><?php esc_html_e('Omniva shipping', 'omnivalt'); ?></h1>
        <p><?php esc_html_e('Manage Omniva shipments, labels and courier collection in one place.', 'omnivalt'); ?></p>
      </div>
      <?php if ( $shipping_settings ) : ?>
        <button id="omniva-call-btn" class="button omnivalt-manifest-page__courier-action" type="button">
          <svg class="omnivalt-manifest-page__truck-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 5h11v11H3V5zm12 4h3l3 3v4h-2a2 2 0 0 1-4 0h-1V9zm2 2v3h2.5L18 11h-1zm-10 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill="currentColor" /></svg>
          <?php esc_html_e('Call Omniva courier', 'omnivalt'); ?>
        </button>
      <?php endif; ?>
    </header>
  <?php if ( ! $shipping_settings ) : ?>
    <?php echo wp_kses_post( OmnivaLt_Helper::build_notice( sprintf( esc_html__('Please configure the plugin on %s.', 'omnivalt'), '<a href="' . esc_url( OmnivaLt_Settings_Page::get_page_url() ) . '">' . esc_html__('Omniva settings page', 'omnivalt') . '</a>' ), 'error', 'Omniva' ) ); ?>
  <?php else : ?>
      <?php if ( ! empty($current_courier_calls) ) : ?>
        <section class="call-courier-container omnivalt-manifest-page__scheduled-card">
          <div class="omnivalt-manifest-page__scheduled-heading">
            <div>
              <span class="dashicons dashicons-clock" aria-hidden="true"></span>
              <h2><?php esc_html_e('Scheduled courier arrivals', 'omnivalt'); ?></h2>
            </div>
            <span class="omnivalt-manifest-page__scheduled-help"><?php echo wp_kses_post( OmnivaLt_Helper::custom_tip( esc_html__('After arrival time expires, the record is automatically removed', 'omnivalt') ) ); ?></span>
          </div>
          <div class="current_calls">
            <table>
              <?php if ( $is_wrong_timezone ) : ?>
              <tr>
                <td colspan="2">
                  <span class="timezone_alert"><?php echo __('The timezone is different!', 'omnivalt') . OmnivaLt_Helper::custom_tip($timezone_alert . '. ' . __('This table shows the real courier call time converted to the timezone of your website', 'omnivalt') . '.'); ?></span>
                </td>
              </tr>
              <?php endif; ?>
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
        </section>
      <?php endif; ?>

      <nav class="omnivalt-manifest-page__tabs" aria-label="<?php esc_attr_e('Shipment order groups', 'omnivalt'); ?>">
        <?php foreach ( $page_params['strings'] as $tab => $tab_title ) : ?>
          <a class="omnivalt-manifest-page__tab <?php echo $orders_data['action'] == $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url(OmnivaLt_Manifest::page_make_link(array('paged' => ($orders_data['action'] == $tab ? $orders_data['paged'] : 1), 'action' => $tab))); ?>"<?php echo $orders_data['action'] == $tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html($tab_title); ?></a>
        <?php endforeach; ?>
      </nav>

      <?php if ( $orders_data['is_orders'] ) : ?>
        <section class="mass-print-container omnivalt-manifest-page__bulk-actions<?php echo ! empty($selected_orders) ? ' is-visible' : ''; ?>">
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
            <span class="title"><?php esc_html_e('Selected', 'omnivalt'); ?><?php echo ($desc) ? '*' : ''; ?>:</span>
            <?php foreach ($selected_orders as $order_id) : ?>
              <span class="item" data-id="<?php echo $order_id; ?>"><?php echo '#' . $order_id; ?><span class="dashicons dashicons-no"></span></span>
            <?php endforeach; ?>
            <?php if ($desc) : ?>
              <span class="desc">*<?php echo $desc; ?></span>
            <?php endif; ?>
          </div>
          <div class="omnivalt-manifest-page__selection-actions<?php echo ! empty($selected_orders) ? ' is-visible' : ''; ?>">
            <?php if ($manifest_enabled) : ?>
              <button id="submit_manifest_items_1" title="<?php echo esc_attr__('Generate manifest', 'omnivalt'); ?>" type="button" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--secondary">
                <?php esc_html_e('Generate manifest', 'omnivalt'); ?>
              </button>
            <?php endif; ?>
            <button id="submit_manifest_labels_1" title="<?php echo esc_attr__('Generate and print labels', 'omnivalt'); ?>" type="button" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary">
              <?php esc_html_e('Generate and print labels', 'omnivalt'); ?>
            </button>
          </div>
        </section>
      <?php endif; ?>

      <section class="table-container omnivalt-manifest-page__orders-card">
        <form id="filter-form" class="" action="<?php echo OmnivaLt_Manifest::page_make_link(array('action' => $orders_data['action'])); ?>" method="POST">
          <?php wp_nonce_field('omnivalt_labels', 'omnivalt_labels_nonce'); ?>
          <div class="omnivalt-manifest-page__filters">
            <div class="omnivalt-manifest-page__filter-field omnivalt-manifest-page__filter-field--id">
              <label for="filter_id"><?php esc_html_e('Order ID', 'omnivalt'); ?></label>
              <input type="text" name="filter_id" id="filter_id" value="<?php echo esc_attr($orders_data['filters']['id']); ?>" placeholder="<?php esc_attr_e('Order ID', 'omnivalt'); ?>" />
            </div>
            <div class="omnivalt-manifest-page__filter-field">
              <label for="filter_customer"><?php esc_html_e('Customer', 'omnivalt'); ?></label>
              <input type="text" name="filter_customer" id="filter_customer" value="<?php echo esc_attr($orders_data['filters']['customer']); ?>" placeholder="<?php esc_attr_e('Customer', 'omnivalt'); ?>" />
            </div>
            <div class="omnivalt-manifest-page__filter-field">
              <label for="filter_barcode"><?php esc_html_e('Barcode', 'omnivalt'); ?></label>
              <input type="text" name="filter_barcode" id="filter_barcode" value="<?php echo esc_attr($orders_data['filters']['barcode']); ?>" placeholder="<?php esc_attr_e('Barcode', 'omnivalt'); ?>" />
            </div>
            <div class="omnivalt-manifest-page__filter-field omnivalt-manifest-page__filter-field--status">
              <label for="filter_status"><?php esc_html_e('Order status', 'omnivalt'); ?></label>
              <select name="filter_status" id="filter_status">
                <option value="-1"><?php echo esc_html(_x('All', 'All status', 'omnivalt')); ?></option>
                <?php foreach ( $orders_data['statuses'] as $status_key => $status ) : ?>
                  <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status_key, $orders_data['filters']['status']); ?>><?php echo esc_html($status); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($manifest_enabled) : ?>
              <div class="omnivalt-manifest-page__filter-field omnivalt-manifest-page__filter-field--date">
                <label><?php esc_html_e('Manifest date', 'omnivalt'); ?></label>
                <div class="datetimepicker">
                  <input name="filter_start_date" type="text" id="datetimepicker1" data-date-format="YYYY-MM-DD" value="<?php echo esc_attr($orders_data['filters']['start_date']); ?>" placeholder="<?php esc_attr_e('From', 'omnivalt'); ?>" autocomplete="off" />
                  <input name="filter_end_date" type="text" id="datetimepicker2" data-date-format="YYYY-MM-DD" value="<?php echo esc_attr($orders_data['filters']['end_date']); ?>" placeholder="<?php esc_attr_e('To', 'omnivalt'); ?>" autocomplete="off" />
                </div>
              </div>
            <?php endif; ?>
            <div class="omnivalt-manifest-page__filter-actions">
              <button class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary" type="submit"><?php esc_html_e('Filter', 'omnivalt'); ?></button>
              <button id="clear_filter_btn" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--text" type="button"><?php esc_html_e('Reset', 'omnivalt'); ?></button>
            </div>
          </div>
          <table class="wp-list-table widefat fixed striped posts">
            <thead>
              <tr class="table-header">
                <td class="manage-column column-cb check-column"><input type="checkbox" class="check-all" aria-label="<?php esc_attr_e('Select all orders on this page', 'omnivalt'); ?>" /></td>
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
                    <?php $label_action = ! empty($barcodes) ? __('Print label', 'omnivalt') : __('Generate label', 'omnivalt'); ?>
                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'omnivalt_labels', 'post' => $order_data->id), admin_url('admin-post.php'))); ?>" class="button action omnivalt-manifest-page__row-action" data-tooltip="<?php echo esc_attr($label_action); ?>" aria-label="<?php echo esc_attr($label_action); ?>">
                      <span class="dashicons <?php echo ! empty($barcodes) ? 'dashicons-printer' : 'dashicons-media-document'; ?>" aria-hidden="true"></span>
                      <span class="screen-reader-text"><?php echo esc_html($label_action); ?></span>
                    </a>
                    <?php if ( ! empty($barcodes) ) : ?>
                      <?php $regenerate_label = __('Regenerate label', 'omnivalt'); ?>
                      <a href="<?php echo esc_url(add_query_arg(array('action' => 'omnivalt_labels', 'post' => $order_data->id, 'process' => 'regenerate'), admin_url('admin-post.php'))); ?>" class="button action omnivalt-manifest-page__row-action" data-tooltip="<?php echo esc_attr($regenerate_label); ?>" aria-label="<?php echo esc_attr($regenerate_label); ?>">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php echo esc_html($regenerate_label); ?></span>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if ( ! $orders_data['orders'] ) : ?>
                <tr>
                  <td colspan="<?php echo $manifest_enabled ? 9 : 8; ?>" class="omnivalt-manifest-page__empty-cell">
                    <div class="omnivalt-manifest-page__empty-state">
                      <img src="<?php echo esc_url(OMNIVALT_URL . 'assets/img/admin/smile.svg'); ?>" alt="" aria-hidden="true" />
                      <p><?php esc_html_e('No orders found', 'woocommerce'); ?></p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
          <?php if ( $orders_data['links'] ) : ?>
            <div class="tablenav omnivalt-manifest-page__pagination">
              <div class="tablenav-pages">
                <?php echo wp_kses_post($orders_data['links']); ?>
              </div>
            </div>
          <?php endif; ?>
        </form>
      </section>

      <?php if ( $orders_data['is_orders'] ) : ?>
        <div class="mass-print-container omnivalt-manifest-page__bottom-actions<?php echo ! empty($selected_orders) ? ' is-visible' : ''; ?>">
          <?php if ($manifest_enabled) : ?>
            <button id="submit_manifest_items_2" title="<?php echo esc_attr__('Generate manifest', 'omnivalt'); ?>" type="button" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--secondary">
              <?php esc_html_e('Generate manifest', 'omnivalt'); ?>
            </button>
          <?php endif; ?>
          <button id="submit_manifest_labels_2" title="<?php echo esc_attr__('Generate and print labels', 'omnivalt'); ?>" type="button" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary">
            <?php esc_html_e('Generate and print labels', 'omnivalt'); ?>
          </button>
        </div>
      <?php endif; ?>

      <!-- Modal Courier call-->
      <div id="omniva-courier-modal" class="modal" role="dialog">
        <!-- Modal content: Call-->
        <div id="modal-content-call" class="modal-content">
          <div class="omnivalt-manifest-page__modal-header">
            <span class="omnivalt-manifest-page__modal-icon" aria-hidden="true"><svg class="omnivalt-manifest-page__truck-icon" viewBox="0 0 24 24" focusable="false"><path d="M3 5h11v11H3V5zm12 4h3l3 3v4h-2a2 2 0 0 1-4 0h-1V9zm2 2v3h2.5L18 11h-1zm-10 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill="currentColor" /></svg></span>
            <div>
              <h2><?php esc_html_e('Call Omniva courier', 'omnivalt'); ?></h2>
              <p><?php esc_html_e('Arrange a courier collection for your selected shipments.', 'omnivalt'); ?></p>
            </div>
          </div>
          <div class="alert-info">
            <p><span><?php esc_html_e('Important!', 'omnivalt'); ?></span> <?php esc_html_e('Latest call for same day pickup is until 3 pm.', 'omnivalt'); ?></p>
            <p><?php esc_html_e('Address and contact information can be changed in Omniva settings.', 'omnivalt'); ?></p>
          </div>
          <form id="omniva-call" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_call_courier" />
            <?php wp_nonce_field('omnivalt_call_courier', 'omnivalt_call_courier_nonce'); ?>
            <div class="omnivalt-manifest-page__courier-details">
              <div><span><?php esc_html_e('Shop name', 'omnivalt'); ?></span><?php echo esc_html($shipping_settings['shop_name']); ?></div>
              <div><span><?php esc_html_e('Shop phone number', 'omnivalt'); ?></span><?php echo esc_html( empty($shipping_settings['shop_mobile']) ? $shipping_settings['shop_phone'] : $shipping_settings['shop_mobile'] ); ?></div>
              <div><span><?php esc_html_e('Shop postcode', 'omnivalt'); ?></span><?php echo esc_html($shipping_settings['shop_postcode']); ?></div>
              <div><span><?php esc_html_e('Shop address', 'omnivalt'); ?></span><?php echo esc_html($shipping_settings['shop_address'] . ', ' . $shipping_settings['shop_city']); ?></div>
              <div><span><?php esc_html_e('Comment', 'omnivalt'); ?></span><?php echo esc_html( ! empty($shipping_settings['pickup_comment']) ? $shipping_settings['pickup_comment'] : '-' ); ?></div>
            </div>
            <table class="omnivalt-manifest-page__courier-form" cellspacing="0">
              <tr>
                <th>
                  <label for="call_quantity"><?php esc_html_e('Number of parcels', 'omnivalt'); ?>:</label>
                </th>
                <td>
                  <input type="number" id="call_quantity" name="call_quantity" min="0" max="29" step="1" value="<?php echo count($selected_orders); ?>"/>
                </td>
              </tr>
              <tr title="<?php echo ($active_omx) ? '' : __('This feature is not available', 'omnivalt'); ?>">
                <th>
                  <label for="call_checkboxes_heavy"><?php esc_html_e('Shipments is heavy', 'omnivalt'); ?>:</label>
                </th>
                <td>
                  <label>
                    <input type="checkbox" id="call_checkboxes_heavy" name="call_checkboxes[]" value="heavy" <?php echo ($active_omx) ? '' : 'disabled'; ?>/>
                    <?php esc_html_e('Shipments weight exceeds 30 kg', 'omnivalt'); ?>
                  </label>
                </td>
              </tr>
              <tr title="<?php echo ($active_omx) ? '' : __('This feature is not available', 'omnivalt'); ?>">
                <th>
                  <label for="call_checkboxes_twoman"><?php esc_html_e('Need two man', 'omnivalt'); ?>:</label>
                </th>
                <td>
                  <label>
                    <input type="checkbox" id="call_checkboxes_twoman" name="call_checkboxes[]" value="twoman" <?php echo ($active_omx) ? '' : 'disabled'; ?>/>
                    <?php esc_html_e('2 people are needed to pick up the shipments', 'omnivalt'); ?>
                  </label>
                </td>
              </tr>
              <?php if ( $is_wrong_timezone ) : ?>
                <tr>
                  <td colspan="2">
                    <span class="alert timezone_alert"><?php echo __('The timezone is different!', 'omnivalt') . OmnivaLt_Helper::custom_tip($timezone_alert); ?></span>
                  </td>
                </tr>
              <?php endif; ?>
            </table>
            <div class="modal-footer">
              <button type="button" id="omniva-call-cancel-btn" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--text"><?php esc_html_e('Cancel', 'omnivalt'); ?></button>
              <button type="submit" id="omniva-call-confirm-btn" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary"><?php esc_html_e('Call Omniva courier', 'omnivalt'); ?></button>
            </div>
          </form>
        </div>
        <!-- Modal content: Cancel-->
        <div id="modal-content-cancel" class="modal-content">
          <div class="omnivalt-manifest-page__modal-header">
            <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            <div>
              <h2><?php esc_html_e('Cancel courier arrival', 'omnivalt'); ?></h2>
              <p><?php esc_html_e('This removes the selected courier collection request.', 'omnivalt'); ?></p>
            </div>
          </div>
          <form id="omniva-cancel" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_cancel_courier" />
            <?php wp_nonce_field('omnivalt_cancel_courier', 'omnivalt_cancel_courier_nonce'); ?>
            <input id="omniva-cancel-id" type="hidden" name="call_id" value="" />
            <p class="omnivalt-manifest-page__modal-confirmation"><?php esc_html_e('Are you sure you want to cancel the courier arrival?', 'omnivalt'); ?></p>
            <div class="modal-footer">
              <button type="button" id="omniva-call-cancel-btn" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--text"><?php esc_html_e('No', 'omnivalt'); ?></button>
              <button type="submit" id="omniva-cancel-confirm-btn" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary"><?php esc_html_e('Cancel Omniva courier', 'omnivalt'); ?></button>
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
  <?php endif; ?>
  </div>
</div>
