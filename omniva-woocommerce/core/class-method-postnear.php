<?php
class OmnivaLt_Method_PostNear extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('post_near');
        $this->setKey('pn');
        $this->setTitle(__('Nearest post office', 'omnivalt'));
        $this->setDescription(__('Activate this service, when you want to send parcels to nearest post office.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setDefaultWeight(100);
    }
}
