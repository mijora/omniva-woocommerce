<?php
class OmnivaLt_Method_LetterCourier extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('letter_courier');
        $this->setKey('lc');
        $this->setType('letter');
        $this->setTitle(__('Letter (Courier)', 'omnivalt'));
        $this->setDescription(__('Activate this service, when you want to send letters.', 'omnivalt') . ' ' . __('Available for Estonian customers only.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setMaxWeight(2);
        $this->setRestrictApi(array('EE'));
        $this->setRestrictCountry(array('EE'));
    }
}
