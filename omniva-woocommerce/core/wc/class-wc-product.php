<?php
class OmnivaLt_Wc_Product
{
    public static function get_product( $wc_product_id )
    {
        if ( empty($wc_product_id) ) {
            return false;
        }

        $wc_product = wc_get_product($wc_product_id);
        if ( ! $wc_product ) {
            return false;
        }

        return $wc_product;
    }

    public static function update_meta( $wc_product_id, $meta_key, $value )
    {
        $wc_product = self::get_product($wc_product_id);
        if ( ! $wc_product ) {
            return false;
        }

        $wc_product->update_meta_data($meta_key, $value);
        $wc_product->save();

        return true;
    }

    public static function get_meta( $wc_product_id, $meta_key )
    {
        $wc_product = self::get_product($wc_product_id);
        if ( ! $wc_product ) {
            return false;
        }

        return $wc_product->get_meta($meta_key, true);
    }
}
