<?php
class OmnivaLt_Method_PostBox extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('post_box');
        $this->setKey('pb');
        $this->setType('letter');
        $this->setTitle(__('Letter', 'omnivalt') . ' (' . __('Express', 'omnivalt') . ')');
        $this->setDescription(__('Activate this service, when you want to send parcels as Express letter.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setMaxWeight(20);
        $this->setRestrictApi(array('EE'));
        $this->setRestrictCountry(array('EE'));
    }
}
