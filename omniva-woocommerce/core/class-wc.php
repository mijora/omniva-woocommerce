<?php
class OmnivaLt_Wc
{
    public static function get_page_recognition_ids( $type_key )
    {
        $all_screen_ids = array(
            'admin_order_edit' => array('shop_order', 'woocommerce_page_wc-orders'),
            'admin_manifest' => array('woocommerce_page_omniva-manifest'),
        );

        return $all_screen_ids[$type_key] ?? false;
    }

    public static function get_post_type_by_id( $page_id )
    {
        return get_post_type($page_id);
    }

    public static function get_current_screen_id()
    {
        $screen = get_current_screen();

        return $screen->id ?? false;
    }
}
