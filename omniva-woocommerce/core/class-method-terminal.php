<?php
class OmnivaLt_Method_Terminal extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('terminal');
        $this->setKey('pt');
        $this->setTitle(__('Parcel terminal', 'omnivalt'));
        $this->setDisplayByCountry(array(
            'FI' => array(
                'title' => 'Matkahuolto ' . strtolower(__('Parcel terminal', 'omnivalt')),
                'prefix' => 'Matkahuolto',
                'title_logo' => 'matkahuolto_logo.svg',
                'map_marker' => 'matkahuolto_icon.svg',
            ),
        ));
        $this->setDescription(__('Activate this service, when you want to send parcels to parcel terminals.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setTerminalsType('terminal');
        $this->setDefaultWeight(30);
        $this->setParams(array(
            'sizes' => array(
                'min' => array(2, 9, 14),
                'S' => array(9, 38, 64),
                'M' => array(19, 38, 64),
                'L' => array(39, 38, 64),
            ),
            'titles' => array(
                'S' => _x('Small', 'Box size', 'omnivalt'),
                'M' => _x('Medium', 'Box size', 'omnivalt'),
                'L' => _x('Large', 'Box size', 'omnivalt'),
            ),
        ));
    }
}
