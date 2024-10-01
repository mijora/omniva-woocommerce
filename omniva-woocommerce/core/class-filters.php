<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Filters
{
    public static function orders_list_per_page()
    {
        return apply_filters('omnivalt_orders_list_per_page', 25);
    }

    public static function settings_coupon_args()
    {
        return apply_filters('omnivalt_settings_coupons_args', array(
            'posts_per_page' => 1000,
        ));
    }
}
