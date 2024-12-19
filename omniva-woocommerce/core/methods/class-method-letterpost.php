<?php
class OmnivaLt_Method_LetterPost extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('letter_post');
        $this->setKey('lp');
        $this->setType('letter');
        $this->setTitle(__('Letter (Post office)', 'omnivalt'));
        $this->setDescription(__('Activate this service, when you want to send letters.', 'omnivalt') . ' ' . __('Available for Estonian customers only.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setTerminalsType('post');
        $this->setMaxWeight(2);
        $this->setRestrictApi(array('EE'));
        $this->setRestrictCountry(array('EE'));
    }
}
