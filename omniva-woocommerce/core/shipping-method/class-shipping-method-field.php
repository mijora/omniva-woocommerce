<?php
defined('OMNIVALT_VERSION') or die();

class OmnivaLt_Shipping_Method_Field
{
    private $_id_separator;
    private $_id_prefix;
    private $_id_ending;

    public function __construct( $id_prefix, $id_ending )
    {
        $this->setIdSeparator('_');
        $this->setIdPrefix($id_prefix);
        $this->setIdEnding($id_ending);
    }

    final public function setIdSeparator( $id_separator )
    {
        $this->_id_separator = $id_separator;
        return $this;
    }

    final public function getIdSeparator()
    {
        return $this->_id_separator;
    }

    final public function setIdPrefix( $id_prefix )
    {
        $this->_id_prefix = $id_prefix;
        return $this;
    }

    final public function getIdPrefix()
    {
        return $this->_id_prefix;
    }

    final public function setIdEnding( $id_ending )
    {
        $this->_id_ending = $id_ending;
        return $this;
    }

    final public function getIdEnding()
    {
        return $this->_id_ending;
    }

    public function buildIdPrefix( $id )
    {
        $id_array = array();
        if ( ! empty($this->getIdPrefix()) ) $id_array[] = $this->getIdPrefix();
        $id_array[] = $id;

        return implode($this->getIdSeparator(), $id_array);
    }

    public function buildIdEnding( $id )
    {
        $id_array = array();
        $id_array[] = $id;
        if ( ! empty($this->getIdEnding()) ) $id_array[] = $this->getIdEnding();

        return implode($this->getIdSeparator(), $id_array);
    }

    public function buildIdFull( $id )
    {
        $id_array = array();
        $id_array[] = $this->buildIdPrefix($id);
        if ( ! empty($this->getIdEnding()) ) $id_array[] = $this->getIdEnding();

        return implode($this->getIdSeparator(), $id_array);
    }
}
