<?php
class OmnivaLt_Method_Logistic extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('logistic');
        $this->setKey('lc');
        $this->setTitle(__('Logistics center', 'omnivalt'));
        $this->setIsShippingMethod(false);
    }
}
