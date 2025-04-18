<?php
namespace Mijora\BoxCalculator;

class Debug
{
    private static $instance;
    private $debug = false;
    private $debug_actions = array();

    public function __construct()
    {
        //Nothing
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Debug();
        }
        return self::$instance;
    }

    public function enable( $enable )
    {
        $this->debug = (bool) $enable;
        return $this;
    }

    public function add( $add_line )
    {
        if ( $this->debug ) {
            $this->debug_actions[] = $add_line;
        }
    }

    public function obj( $object )
    {
        return PHP_EOL . print_r($object, true);
    }

    public function end( $end_txt = '' )
    {
        $line = '*****************';
        $txt = (! empty($end_txt)) ? ' END OF ' . $end_txt . ' ' : '';

        $this->add($line . $txt . $line);
    }

    public function getActions()
    {
        return $this->debug_actions;
    }
}
