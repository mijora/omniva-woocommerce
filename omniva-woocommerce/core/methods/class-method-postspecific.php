<?php
class OmnivaLt_Method_PostSpecific extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('post_specific');
        $this->setKey('ps');
        $this->setTitle(__('Specific post office', 'omnivalt'));
        $this->setDescription(__('Activate this service, when you want to send parcels to specific post office.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setDefaultWeight(100);
    }
}
