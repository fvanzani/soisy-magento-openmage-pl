<?php
class Soisy_SoisyPayment_Block_Form_SoisyPayment extends Mage_Payment_Block_Form
{
    protected $helper;

    public function __construct()
    {
        $this->helper=Mage::helper('soisypayment');
        parent::__construct();
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('soisypayment/form/soisypayment.phtml');
    }

    protected function getShopIdSimulation()
    {
        return $this->helper->getShopIdSimulation();
    }

    protected function getAmount()
    {
        $quote=Mage::getModel('checkout/cart')->getQuote();
        $grandTotal = (float) $quote->getgrandTotal();
        return round($grandTotal,0);
    }

    protected function getInstalments()
    {
        return $this->helper->getInstalments();
    }

    protected function getZeroInterestRate()
    {
        return $this->helper->getZeroInterestRate() ? "true" : "false" ;
    }

    protected function isSimulationEnabled()
    {
        return $this->helper->isSimulationEnabled();
    }
}