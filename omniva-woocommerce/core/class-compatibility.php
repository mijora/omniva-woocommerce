<?php
class OmnivaLt_Compatibility
{
    public static function get_terms( $taxonomy, $args = array() )
    {
        if ( defined('ICL_SITEPRESS_VERSION') ) {
            return self::get_terms_WPML($taxonomy, $args);
        }

        $args['taxonomy'] = $taxonomy;
        return get_terms($args);
    }

    private static function get_terms_WPML( $taxonomy, $args = array() )
    {
        global $sitepress;

        $has_get_terms_args_filter = remove_filter( 'get_terms_args', array( $sitepress, 'get_terms_args_filter' ) );
        $has_get_term_filter       = remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );
        $has_terms_clauses_filter  = remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
    
        $terms = get_terms( $taxonomy , $args );
    
        if ( $has_terms_clauses_filter ) {
            add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 3 );
        }
        if ( $has_get_term_filter ) {
            add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
        }
        if ( $has_get_terms_args_filter ) {
            add_filter( 'get_terms_args', array( $sitepress, 'get_terms_args_filter' ), 10, 2 );
        }

        return $terms;
    }
}
