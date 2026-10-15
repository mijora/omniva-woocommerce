<?php
if ( ! defined('ABSPATH') ) {
  exit; // Exit if accessed directly
}

$page_data = isset($page_data) && is_array($page_data) ? $page_data : array();
$shipping_settings = isset($page_data['shipping_settings']) ? $page_data['shipping_settings'] : false;
$configs = isset($page_data['configs']) && is_array($page_data['configs']) ? $page_data['configs'] : array();
$page_params = isset($page_data['page_params']) && is_array($page_data['page_params']) ? $page_data['page_params'] : array();
$orders_data = isset($page_data['orders_data']) && is_array($page_data['orders_data']) ? $page_data['orders_data'] : array();
$selected_orders = isset($page_data['selected_orders']) && is_array($page_data['selected_orders']) ? $page_data['selected_orders'] : array();
$selected_order_has_barcodes = isset($page_data['selected_order_has_barcodes']) && is_array($page_data['selected_order_has_barcodes']) ? $page_data['selected_order_has_barcodes'] : array();
$sender_info_complete = ! empty($page_data['sender_info_complete']);
$sender_info_tooltip = __('Please fill in the sender information on the settings page.', 'omnivalt');
$labels_button_label = __('Generate and print labels', 'omnivalt');
$has_selected_existing_labels = false;
foreach ( $selected_orders as $selected_order_id ) {
  if ( ! empty($selected_order_has_barcodes[(string) $selected_order_id]) ) {
    $has_selected_existing_labels = true;
    break;
  }
}
$labels_bulk_disabled = ! $sender_info_complete && ! $has_selected_existing_labels;
$manifest_enabled = ! empty($page_data['manifest_enabled']);
$active_omx = ! empty($page_data['active_omx']);
$current_courier_calls = isset($page_data['current_courier_calls']) && is_array($page_data['current_courier_calls']) ? $page_data['current_courier_calls'] : array();
$active_filter_count = isset($page_data['active_filter_count']) ? (int) $page_data['active_filter_count'] : 0;
$is_wrong_timezone = ! empty($page_data['is_wrong_timezone']);
$timezone_alert = isset($page_data['timezone_alert']) ? $page_data['timezone_alert'] : '';
$manifest_script_class = wp_script_is('omniva_manifest', 'enqueued') ? '' : ' is-ready';
?>

<style id="omnivalt-manifest-page__critical-mobile-filter">
  /* Keep the page hidden until the document is ready or the fallback opens it. */
  .page-omniva_manifest .omnivalt-manifest-page__root:not(.is-ready) {
    visibility: hidden;
  }
</style>

<noscript>
  <style>
    .page-omniva_manifest .omnivalt-manifest-page__root {
      visibility: visible !important;
    }
  </style>
</noscript>

<div class="wrap page-omniva_manifest omnivalt-manifest-page">
  <div id="omnivalt-manifest-root" class="omnivalt-manifest-page__root has-mobile-order-toggle<?php echo esc_attr($manifest_script_class); ?>">
    <header class="omnivalt-manifest-page__header">
      <div>
        <div class="omnivalt-manifest-page__breadcrumb">
          <span><?php esc_html_e('WooCommerce', 'omnivalt'); ?></span>
          <span aria-hidden="true">/</span>
          <span><?php esc_html_e('Omniva', 'omnivalt'); ?></span>
        </div>
        <h1><?php esc_html_e('Omniva delivery', 'omnivalt'); ?></h1>
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
                  <span class="timezone_alert"><?php echo wp_kses_post( esc_html__('The timezone is different!', 'omnivalt') . OmnivaLt_Helper::custom_tip( esc_html($timezone_alert . '. ' . esc_html__('This table shows the real courier call time converted to the timezone of your website', 'omnivalt') . '.') ) ); ?></span>
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
                  <td><?php echo wp_kses_post($call_string); ?></td>
                  <td>
                    <input type="hidden" name="call_id" value="<?php echo esc_html($call['id']); ?>" />
                    <button class="icon-btn action-cancel" value="cancel" title="<?php esc_attr_e('Cancel this call', 'omnivalt'); ?>"><span class="dashicons dashicons-no"></span></button>
                    <button class="icon-btn action-remove" value="remove" title="<?php esc_attr_e('Courier arrived and this can be removed', 'omnivalt'); ?>"><span class="dashicons dashicons-minus"></span></button>
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
      <div class="omnivalt-manifest-page__mobile-controls">
        <select id="omnivalt-manifest-page__tabs-select" class="omnivalt-manifest-page__tabs-select" aria-label="<?php esc_attr_e('Shipment order groups', 'omnivalt'); ?>">
          <?php foreach ( $page_params['strings'] as $tab => $tab_title ) : ?>
            <option value="<?php echo esc_url(OmnivaLt_Manifest::page_make_link(array('paged' => ($orders_data['action'] == $tab ? $orders_data['paged'] : 1), 'action' => $tab))); ?>" <?php selected($orders_data['action'], $tab); ?>><?php echo esc_html($tab_title); ?></option>
          <?php endforeach; ?>
        </select>
        <button id="omnivalt-manifest-page__filter-toggle" type="button" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary" aria-controls="omnivalt-manifest-page__filters" aria-expanded="false">
          <?php esc_html_e('Filter', 'omnivalt'); ?>
          <?php if ( $active_filter_count > 0 ) : ?>
            <span class="omnivalt-manifest-page__filter-count" aria-label="<?php echo esc_attr(sprintf(__('Active filters: %d', 'omnivalt'), $active_filter_count)); ?>"><?php echo esc_html((string) $active_filter_count); ?></span>
          <?php endif; ?>
        </button>
      </div>
      <div class="omnivalt-manifest-page__filter-backdrop" aria-hidden="true"></div>

      <?php if ( $orders_data['is_orders'] ) : ?>
        <?php
        $selected_order_count = count($selected_orders);
        $has_many_selected = $selected_order_count > 3;
        ?>
        <section class="mass-print-container omnivalt-manifest-page__bulk-actions<?php echo ! empty($selected_orders) ? ' is-visible' : ''; ?><?php echo $has_many_selected ? ' has-many-selected' : ''; ?>" data-sender-info-complete="<?php echo esc_attr($sender_info_complete ? '1' : '0'); ?>">
          <form id="manifest-print-form" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_manifest" />
            <?php wp_nonce_field('omnivalt_manifest', 'omnivalt_manifest_nonce'); ?>
          </form>
          <form id="labels-print-form" action="admin-post.php" method="GET">
            <input type="hidden" name="action" value="omnivalt_labels" />
            <?php wp_nonce_field('omnivalt_labels', 'omnivalt_labels_nonce'); ?>
          </form>
          <div id="selected-orders" class="selected-orders" style="<?php echo (empty($selected_orders)) ? 'display:none' : ''; ?>">
            <span class="title"><?php esc_html_e('Selected', 'omnivalt'); ?>:</span>
            <span class="selected-count" aria-live="polite"><?php echo esc_html((string) $selected_order_count); ?></span>
            <?php foreach ($selected_orders as $order_id) : ?>
              <span class="item" data-id="<?php echo esc_attr($order_id); ?>" data-has-barcodes="<?php echo ! empty($selected_order_has_barcodes[(string) $order_id]) ? '1' : '0'; ?>">#<?php echo esc_html($order_id); ?><span class="dashicons dashicons-no"></span></span>
            <?php endforeach; ?>
          </div>
          <div class="omnivalt-manifest-page__selection-actions<?php echo ! empty($selected_orders) ? ' is-visible' : ''; ?>">
            <?php if ($manifest_enabled) : ?>
              <button id="submit_manifest_items_1" title="<?php echo esc_attr__('Generate manifest', 'omnivalt'); ?>" type="button" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--secondary">
                <?php esc_html_e('Generate manifest', 'omnivalt'); ?>
              </button>
            <?php endif; ?>
            <?php if ( ! $sender_info_complete ) : ?>
              <span class="omnivalt-manifest-page__tooltip-trigger" data-tooltip="<?php echo esc_attr($labels_bulk_disabled ? $sender_info_tooltip : ''); ?>" data-sender-tooltip="<?php echo esc_attr($sender_info_tooltip); ?>" tabindex="0" role="group" aria-label="<?php echo esc_attr($labels_bulk_disabled ? $sender_info_tooltip : $labels_button_label); ?>">
            <?php endif; ?>
              <button id="submit_manifest_labels_1" title="<?php echo esc_attr($labels_button_label); ?>" type="button"<?php if ( $labels_bulk_disabled ) : ?> disabled="disabled"<?php endif; ?> class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary">
                <?php echo esc_html($labels_button_label); ?>
              </button>
            <?php if ( ! $sender_info_complete ) : ?>
              </span>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>

      <section class="table-container omnivalt-manifest-page__orders-card">
        <form id="filter-form" class="" action="<?php echo esc_url(OmnivaLt_Manifest::page_make_link(array('action' => $orders_data['action']))); ?>" method="POST">
          <?php wp_nonce_field('omnivalt_labels', 'omnivalt_labels_nonce'); ?>
          <div id="omnivalt-manifest-page__filters" class="omnivalt-manifest-page__filters" role="dialog" aria-label="<?php esc_attr_e('Filters', 'omnivalt'); ?>">
            <button id="omnivalt-manifest-page__filter-close" type="button" class="omnivalt-manifest-page__filter-close" aria-label="<?php esc_attr_e('Close filters', 'omnivalt'); ?>">
              <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            </button>
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
                <td class="manage-column column-cb check-column">
                  <input type="checkbox" class="check-all" aria-label="<?php esc_attr_e('Select all orders on this page', 'omnivalt'); ?>" />
                  <span class="omnivalt-manifest-page__mobile-select-all"><?php esc_html_e('Select all orders on this page', 'omnivalt'); ?></span>
                </td>
                <th scope="col" class="column-order_id"><?php esc_html_e('ID', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Customer', 'omnivalt'); ?></th>
                <th scope="col" class="column-order_status"><?php esc_html_e('Order Status', 'omnivalt'); ?></th>
                <th scope="col" class="column-order_info"><?php esc_html_e('Order information', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Service', 'omnivalt'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Barcode', 'omnivalt'); ?></th>
                <?php if ($manifest_enabled) : ?>
                  <th scope="col" class="column-manifest_date"><?php esc_html_e('Manifest date', 'omnivalt'); ?></th>
                <?php endif; ?>
                <th scope="col" class="manage-column"><?php esc_html_e('Actions', 'omnivalt'); ?></th>
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
                $mobile_detail_ids = array(
                  'omniva-order-' . $order_data->id . '-info',
                  'omniva-order-' . $order_data->id . '-service',
                  'omniva-order-' . $order_data->id . '-barcode',
                );
                if ( $manifest_enabled ) {
                  $mobile_detail_ids[] = 'omniva-order-' . $order_data->id . '-manifest-date';
                }
                ?>
                <?php if ( OmnivaLt_Manifest::is_mannifest_orders_table($orders_data['action']) && $date_tracker !== $date ) : ?>
                  <tr class="omnivalt-manifest-page__date-row">
                    <?php $colspan = ($manifest_enabled) ? 9 : 8; ?>
                    <td colspan="<?php echo esc_attr((string) $colspan); ?>" class="manifest-date-title">
                      <?php $date_tracker = $manifest_date; echo esc_html($date_tracker); ?>
                    </td>
                  </tr>
                <?php endif; ?>
                <tr class="data-row">
                  <?php $checked = (in_array((int) $order_data->id, $selected_orders, true)) ? 'checked' : ''; ?>
                  <th scope="row" class="check-column"><input type="checkbox" name="items[]" class="manifest-item" value="<?php echo esc_attr($order_data->id); ?>" data-has-barcodes="<?php echo ! empty($barcodes) ? '1' : '0'; ?>" <?php echo esc_attr($checked); ?>/></th>
                  <td class="manage-column column-order_id">
                    <div class="omnivalt-manifest-page__mobile-order-identity">
                      <a href="<?php echo esc_url($order_data->admin->url_edit); ?>">#<?php echo esc_html($order_data->number); ?></a>
                    </div>
                  </td>
                  <td class="column-order_customer">
                    <div class="data-grid-cell-content">
                      <span class="customer-name"><?php echo esc_html(OmnivaLt_Order::get_customer_fullname($order_data)); ?></span>
                      <span class="customer-company"><?php echo esc_html(OmnivaLt_Order::get_customer_company($order_data)); ?></span>
                    </div>
                    <button
                      type="button"
                      class="omnivalt-manifest-page__mobile-details"
                      aria-expanded="false"
                      aria-controls="<?php echo esc_attr(implode(' ', $mobile_detail_ids)); ?>"
                      data-show-label="<?php esc_attr_e('Show details', 'omnivalt'); ?>"
                      data-hide-label="<?php esc_attr_e('Hide details', 'omnivalt'); ?>"
                    >
                      <span><?php esc_html_e('Show details', 'omnivalt'); ?></span>
                      <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                    </button>
                  </td>
                  <td class="omnivalt-manifest-page__mobile-order-amount">
                    <span><?php esc_html_e('Amount', 'omnivalt'); ?></span>
                    <strong><?php echo wp_kses_post( OmnivaLt_Order::get_price_text($order_data->payment->total) ); ?></strong>
                  </td>
                  <td class="column-order_status">
                    <div class="data-grid-cell-content">
                      <mark class="order-status status-<?php echo esc_attr($order_data->status); ?>">
                        <span><?php echo esc_html(wc_get_order_status_name($order_data->status)); ?></span>
                      </mark>
                    </div>
                    <div class="omnivalt-manifest-page__mobile-order-date">
                      <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                      <span><?php echo esc_html($order_data->created); ?></span>
                    </div>
                  </td>
                  <td id="<?php echo esc_attr($mobile_detail_ids[0]); ?>" class="column-order_info omnivalt-manifest-page__mobile-detail" data-mobile-label="<?php esc_attr_e('Order information', 'omnivalt'); ?>">
                    <div class="data-grid-cell-content omnivalt-manifest-page__desktop-order-value">
                      <b><?php esc_html_e('Date', 'omnivalt'); ?>:</b>
                      <span><?php echo esc_html($order_data->created); ?></span>
                    </div>
                    <div class="data-grid-cell-content omnivalt-manifest-page__desktop-order-value">
                      <b><?php esc_html_e('Amount', 'omnivalt'); ?>:</b>
                      <span><?php echo wp_kses_post( OmnivaLt_Order::get_price_text($order_data->payment->total) ); ?></span>
                    </div>
                    <div class="data-grid-cell-content omnivalt-manifest-page__mobile-secondary">
                      <b><?php esc_html_e('Weight', 'omnivalt'); ?>:</b> <?php echo esc_html(OmnivaLt_Order::get_weight_text($order_size)); ?>
                    </div>
                    <div class="data-grid-cell-content omnivalt-manifest-page__mobile-secondary">
                      <b><?php esc_html_e('Size', 'omnivalt'); ?>:</b> <?php echo esc_html(OmnivaLt_Order::get_dimmension_text($order_size)); ?>
                    </div>
                    <div class="data-grid-cell-content omnivalt-manifest-page__mobile-secondary">
                      <b><?php esc_html_e('Total shipments', 'omnivalt'); ?>:</b> <?php echo esc_html((string) ((! empty($total_shipments)) ? $total_shipments : 1)); ?>
                    </div>
                  </td>
                  <td id="<?php echo esc_attr($mobile_detail_ids[1]); ?>" class="manage-column column-order_service omnivalt-manifest-page__mobile-detail" data-mobile-label="<?php esc_attr_e('Service', 'omnivalt'); ?>">
                    <div class="data-grid-cell-content">
                      <?php OmnivaLt_Order::admin_order_display($order_data->id, false); ?>
                    </div>
                  </td>
                  <td id="<?php echo esc_attr($mobile_detail_ids[2]); ?>" class="manage-column column-order_barcode omnivalt-manifest-page__mobile-detail" data-mobile-label="<?php esc_attr_e('Barcode', 'omnivalt'); ?>">
                    <div class="data-grid-cell-content">
                      <?php if ( ! empty($barcodes) ) : ?>
                        <?php foreach ( $barcodes as $barcode ) : ?>
                          <?php do_action('print_omniva_tracking_url', $barcode, $shipping_settings['shop_countrycode']); ?>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      <?php $error = $order_data->omniva->error; ?>
                      <?php if ( $error ) : ?>
                        <?php if ( ! empty($barcodes) ) : ?><br /><?php endif; ?>
                        <span><b><?php esc_html_e('Error', 'omnivalt'); ?>:</b> <?php echo esc_html($error); ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <?php if ($manifest_enabled) : ?>
                    <td id="<?php echo esc_attr($mobile_detail_ids[3]); ?>" class="column-manifest_date omnivalt-manifest-page__mobile-detail" data-mobile-label="<?php esc_attr_e('Manifest date', 'omnivalt'); ?>">
                      <div class="data-grid-cell-content">
                        <?php echo esc_html($manifest_date); ?>
                      </div>
                    </td>
                  <?php endif; ?>
                  <td class="manage-column column-order_actions">
                    <span class="omnivalt-manifest-page__mobile-actions-label"><?php esc_html_e('Actions', 'omnivalt'); ?></span>
                    <?php $label_action = ! empty($barcodes) ? __('Print label', 'omnivalt') : __('Generate label', 'omnivalt'); ?>
                    <?php $label_action_disabled = ! $sender_info_complete && empty($barcodes); ?>
                    <a
                      <?php if ( ! $label_action_disabled ) : ?>href="<?php echo esc_url(add_query_arg(array('action' => 'omnivalt_labels', 'post' => $order_data->id), admin_url('admin-post.php'))); ?>"<?php endif; ?>
                      class="button action omnivalt-manifest-page__row-action<?php echo $label_action_disabled ? ' is-disabled' : ''; ?>"
                      data-tooltip="<?php echo esc_attr($label_action_disabled ? $sender_info_tooltip : $label_action); ?>"
                      aria-label="<?php echo esc_attr($label_action); ?>"
                      <?php if ( $label_action_disabled ) : ?>aria-disabled="true" tabindex="-1"<?php endif; ?>>
                      <span class="dashicons <?php echo ! empty($barcodes) ? 'dashicons-printer' : 'dashicons-media-document'; ?>" aria-hidden="true"></span>
                      <span class="screen-reader-text"><?php echo esc_html($label_action); ?></span>
                    </a>
                    <?php if ( ! empty($barcodes) ) : ?>
                      <?php $regenerate_label = __('Regenerate label', 'omnivalt'); ?>
                      <a
                        <?php if ( $sender_info_complete ) : ?>href="<?php echo esc_url(add_query_arg(array('action' => 'omnivalt_labels', 'post' => $order_data->id, 'process' => 'regenerate'), admin_url('admin-post.php'))); ?>"<?php endif; ?>
                        class="button action omnivalt-manifest-page__row-action<?php echo $sender_info_complete ? '' : ' is-disabled'; ?>"
                        data-tooltip="<?php echo esc_attr($sender_info_complete ? $regenerate_label : $sender_info_tooltip); ?>"
                        aria-label="<?php echo esc_attr($regenerate_label); ?>"
                        <?php if ( ! $sender_info_complete ) : ?>aria-disabled="true" tabindex="-1"<?php endif; ?>>
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php echo esc_html($regenerate_label); ?></span>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if ( ! $orders_data['orders'] ) : ?>
                <tr class="omnivalt-manifest-page__empty-row">
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
        <div class="mass-print-container omnivalt-manifest-page__bottom-actions<?php echo ! empty($selected_orders) ? ' is-visible' : ''; ?>" data-sender-info-complete="<?php echo esc_attr($sender_info_complete ? '1' : '0'); ?>">
          <?php if ($manifest_enabled) : ?>
            <button id="submit_manifest_items_2" title="<?php echo esc_attr__('Generate manifest', 'omnivalt'); ?>" type="button" class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--secondary">
              <?php esc_html_e('Generate manifest', 'omnivalt'); ?>
            </button>
          <?php endif; ?>
          <?php if ( ! $sender_info_complete ) : ?>
            <span class="omnivalt-manifest-page__tooltip-trigger" data-tooltip="<?php echo esc_attr($labels_bulk_disabled ? $sender_info_tooltip : ''); ?>" data-sender-tooltip="<?php echo esc_attr($sender_info_tooltip); ?>" tabindex="0" role="group" aria-label="<?php echo esc_attr($labels_bulk_disabled ? $sender_info_tooltip : $labels_button_label); ?>">
          <?php endif; ?>
            <button id="submit_manifest_labels_2" title="<?php echo esc_attr($labels_button_label); ?>" type="button"<?php if ( $labels_bulk_disabled ) : ?> disabled="disabled"<?php endif; ?> class="button omnivalt-manifest-page__button omnivalt-manifest-page__button--primary">
              <?php echo esc_html($labels_button_label); ?>
            </button>
          <?php if ( ! $sender_info_complete ) : ?>
            </span>
          <?php endif; ?>
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
              <tr title="<?php echo ($active_omx) ? '' : esc_attr__('This feature is not available', 'omnivalt'); ?>">
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
              <tr title="<?php echo ($active_omx) ? '' : esc_attr__('This feature is not available', 'omnivalt'); ?>">
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
                    <span class="alert timezone_alert"><?php echo wp_kses_post( esc_html__('The timezone is different!', 'omnivalt') . OmnivaLt_Helper::custom_tip(esc_html($timezone_alert)) ); ?></span>
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
