<?php
class OmnivaLt_Method_CourierPlus extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('courier_plus');
        $this->setKey('cp');
        $this->setTitle(__('Courier', 'omnivalt'));
        $this->setDescription(__('Activate this service, when your e-shop customers would like to receive parcels in Estonia.', 'omnivalt') . ' ' . __('Available for Estonian customers only.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setDefaultWeight(100);
    }
}
