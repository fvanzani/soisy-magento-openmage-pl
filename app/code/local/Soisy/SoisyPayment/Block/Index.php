<?php
class Soisy_SoisyPayment_Block_Index extends Mage_Core_Block_Template{

    protected $helper;

    /**
     * Soisy_SoisyPayment_Block_Index constructor.
     */
    public function __construct()
    {
        $this->helper=Mage::helper('soisypayment');
    }

    public function getDescription()
    {
        if ($this->getError()) {
            return $this->helper->getGeneralConfig("error_description");
        }
        return $this->helper->getGeneralConfig("success_description");
    }

}