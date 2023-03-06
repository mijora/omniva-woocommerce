<?php
class OmnivaLt_Configs
{
    /**
     * Find method by array key or by array value "key"
     * 
     * @param string $key - method key
     * @return array|boolean
     */
    public static function find_method( $key )
    {
        $all_methods_params = OmnivaLt_Core::get_configs('method_params');

        foreach ( $all_methods_params as $method_name => $method_params ) {
            if ( $key == $method_name || $key == $method_params['key'] ) {
                return $all_methods_params[$method_name];
            }
        }

        return false;
    }

    /**
     * Get terminals type if method have terminals
     * 
     * @param string $key - method key
     * @return string|boolean
     */
    public static function get_method_terminals_type( $method_key )
    {
        $method = self::find_method($method_key);

        if ( isset($method['terminals_type']) && $method['terminals_type'] != 'courier' ) {
            return $method['terminals_type'];
        }

        return false;
    }

    /**
     * Get method title
     * 
     * @param string $key - method key
     * @return string
     */
    public static function get_method_title( $method_key )
    {
        $method = self::find_method($method_key);

        if ( ! $method ) {
            return $method_key;
        }

        return $method['title'] ?? $method_key;
    }
}
