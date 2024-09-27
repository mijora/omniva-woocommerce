<?php
class OmnivaLt_Method_Courier extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('courier');
        $this->setKey('c');
        $this->setTitle(__('Courier Baltic', 'omnivalt'));
        $this->setDescription(__('Activate this service, when you want to send parcels within Latvia and Lithuania.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setDefaultWeight(100);
    }
}
