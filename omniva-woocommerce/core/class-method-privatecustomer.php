<?php
class OmnivaLt_Method_PrivateCustomer extends OmnivaLt_Method_Core
{
    public function __construct()
    {
        $this->setId('private_customer');
        $this->setKey('pc');
        $this->setTitle(__('Private customer', 'omnivalt'));
        $this->setDisplayByCountry(array(
            'FI' => array(
                'title' => __('Courier Finland', 'omnivalt'),
                'front_title' => __('Courier Finland', 'omnivalt'),
            ),
        ));
        $this->setDescription(__('Activate this service, when you want to send parcels to private persons in Finland.', 'omnivalt')  . ' ' . __('Available for Estonian customers only.', 'omnivalt'));
        $this->setIsShippingMethod(true);
        $this->setDefaultWeight(100);
    }
}
