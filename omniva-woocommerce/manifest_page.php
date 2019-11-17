<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wrap">
<h1><?php _e('Omniva manifest','omnivalt'); ?></h1>
<?php
global $wpdb;

// The SQL query
$items = $wpdb->get_results( "
    SELECT wpp.*
    FROM {$wpdb->prefix}posts as wpp
    LEFT JOIN {$wpdb->prefix}woocommerce_order_items as woi ON woi.order_id = wpp.id
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woim ON woim.order_item_id = woi.order_item_id AND woim.meta_key = 'method_id'
    WHERE woim.meta_value IN ('omnivalt_pt','omnivalt_c','omnivalt')
" );
?>

<div>
  <button id="omniva-call-btn" class="button action"><?php _e('Call Omniva courier','omnivalt') ?></button>
</div>

<?php if(count($items)): ?>
<?php
//group items by shipping date DESC
$grouped_items = array();
foreach ($items as $item){
  $generation_date = get_post_meta($item->ID,'_manifest_generation_date',true);
  if (!$generation_date)
    $date = "new";
  else
    $date = date('Y-m-d H:i',strtotime($generation_date));
  if (!isset($grouped_items[$date])) $grouped_items[$date] = array();
  $grouped_items[$date][] = $item;
}
krsort($grouped_items);
$items_ignore = array();
?>
<?php
$p_limit = 4;
$total_pages = ceil(count($grouped_items)/$p_limit);
$current_page = 1;

if (isset($_GET['paged']))
  $current_page = $_GET['paged'];
if ($current_page > $total_pages) $current_page = $total_pages;
$counter = 0;
?>
<?php
$page_links = paginate_links( array(
    'base' => add_query_arg( 'paged', '%#%' ),
    'format' => '?paged=%#%',
    'prev_text' => __( '&laquo;', 'text-domain' ),
    'next_text' => __( '&raquo;', 'text-domain' ),
    'total' => $total_pages,
    'current' => $current_page,
    'type' => 'plain'
) );

if ( $page_links ) {
    echo '<div class="tablenav" style = "float:left;"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div><div class = "clear"></div>';
}
?>
<?php foreach ($grouped_items as $date=>$orders): ?>
<?php
  $counter++;
  if ($current_page*$p_limit-$p_limit >= $counter) continue;
  if (($current_page)*$p_limit < $counter) break;
?>
<br/>
<h3><?php echo ($date=="new"?__('New orders','omnivalt'):$date); ?></h3>
<div class = "">
<table class="wp-list-table widefat fixed striped posts">
    <thead>
    <tr >
      <td class = "manage-column column-cb check-column"><input type = "checkbox"  class = "check-all"/></td>
      <th class = "manage-column"><?php echo __('Order #','omnivalt');?></th>
      <th class = "manage-column"><?php echo __('Manifest generation date','omnivalt'); ?></th>
      <th></th>
    </tr>
    </thead>
    <tbody>
        <?php $_odd = ''; ?>
        <?php foreach ($orders as $order): ?>
        <tr class = "data-row">
            <?php 
            $order_items = array();
            $orderObj = wc_get_order( $order->ID );
            ?>
            <th class = "check-column"><input type = "checkbox" name = "items[]" class = "manifest-item" value = "<?php echo $order->ID; ?>"/></th>
            <td>
              <div class = "data-grid-cell-content">
              <?php
                $link = '<a href="'. $orderObj->get_edit_order_url() .'" >';
                $link .= '#' . $order->ID. ' ' . $orderObj->get_billing_first_name() . ' ' . $orderObj->get_billing_last_name();
                $link .= '</a>';
                echo $link;
              ?>
              </div>
            </td>
            <td><div class = "data-grid-cell-content"><?php echo get_post_meta($order->ID,'_manifest_generation_date',true); ?></div></td>
            <td>
            <form action = "admin-post.php" method = "GET">
              <input type = "hidden" name = "action" value = "omnivalt_labels"/>
              <input type="hidden" name="post[]" value = "<?php echo $order->ID; ?>" />
              <?php wp_nonce_field( 'omnivalt_labels', 'omnivalt_labels_nonce' ); ?>
               <button title="<?php echo __('Print label','omnivalt');?>" type="submit" class="button action">
                <?php echo __('Print labels','omnivalt');?>
              </button>
            </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endforeach; ?>
<div>
<br/>
  <div class="f-left">
    <form id = "manifest-print-form" action = "admin-post.php" method = "GET">
      <input type = "hidden" name = "action" value = "omnivalt_manifest"/>
      <?php wp_nonce_field( 'omnivalt_manifest', 'omnivalt_manifest_nonce' ); ?>
    </form>
    <form id = "labels-print-form" action = "admin-post.php" method = "GET">
      <input type = "hidden" name = "action" value = "omnivalt_labels"/>
      <?php wp_nonce_field( 'omnivalt_labels', 'omnivalt_labels_nonce' ); ?>
    </form>
    <button id="submit_manifest_items" title="<?php echo __('Generate manifest','omnivalt');?>" type="button" class="button action">
      <?php echo __('Generate manifest','omnivalt');?>
    </button>    
    <button id="submit_manifest_labels" title="<?php echo __('Print labels','omnivalt');?>" type="button" class="button action">
      <?php echo __('Print labels','omnivalt');?>
    </button>     
  </div>
  <div class="f-clear"></div>
</div>

<!-- Modal Carier call-->
<div id="omniva-courier-modal" class="modal fade" role="dialog">
  <!-- Modal content-->
  <div class="modal-content">
    <div class="alert-info">
      <p><span><?php _e('Important!', 'omnivalt') ?></span> <?php _e('Latest call for same day pickup is until 3 pm.', 'omnivalt') ?></p>
      <p><?php _e('Address and contact information can be changed in Omniva settings.', 'omnivalt') ?></p>
    </div>
    <form id="omniva-call" action = "admin-post.php" method = "GET">
      <input type = "hidden" name = "action" value = "omnivalt_call_courier"/>
      <?php wp_nonce_field( 'omnivalt_call_courier', 'omnivalt_call_courier_nonce' ); ?>
      <?php do_action('get_omniva_info_for_courier'); ?>
      <div class="modal-footer">
        <button type="submit" id="omniva-call-btn" class="button action"><?php _e('Call Omniva courier', 'omnivalt') ?></button>
        <button type="button" id="omniva-call-cancel-btn" class="button action"><?php _e('Cancel') ?></button>
      </div>
    </form>
  </div>
</div>
<!--/ Modal Carier call-->

<script>
jQuery('document').ready(function($){

  $('#omniva-courier-modal').on('click', function(e){
    if (e.target === this) {
      $('#omniva-courier-modal').removeClass('open');
    }
  });

  $('#omniva-call-btn').on('click', function(e){
    e.preventDefault();
    $('#omniva-courier-modal').addClass('open');
  });

  $('#omniva-call-cancel-btn').on('click', function(e){
    e.preventDefault();
    $('#omniva-courier-modal').removeClass('open');
  });

  $('#submit_manifest_items').on('click',function(){
    var ids = "";
    $('#manifest-print-form .post_id').remove();
    $('.manifest-item:checked').each(function() {
      ids += $(this).val()+";";
      var id = $(this).val();
       $('#manifest-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "'+id+'" />');
    });
    $('#item_ids').val(ids);
    if (ids == ""){
      alert('<?php echo __('Select orders','omnivalt'); ?>');
    } else {
      $('#manifest-print-form').submit();
    }
    //console.log($('#item_ids').val());
    
  });
  
  $('#submit_manifest_labels').on('click',function(){
    var ids = "";
    $('#labels-print-form .post_id').remove();
    $('.manifest-item:checked').each(function() {
      ids += $(this).val()+";";
      var id = $(this).val();
       $('#labels-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "'+id+'" />');
    });
    if (ids == ""){
      alert('<?php echo __('Select orders','omnivalt'); ?>');
    } else {
      $('#labels-print-form').submit();
    }
    //console.log($('#item_ids').val());

  });
    $('.check-all').on('click',function(){
      var checked = $(this).prop('checked');
      $(this).parents('table').find('.manifest-item').each(function() {
        $(this).prop('checked', checked);
      });
    });
});
</script>
<?php else: ?>
    <p><?php echo __('No assign shipments found','omnivalt'); ?></p>
<?php endif ?>
  
</div>