<?php
class Soisy_SoisyPayment_Block_Simulation extends Mage_Payment_Block_Form
{
    protected $helper;

    public function __construct()
    {
        $this->helper=Mage::helper('soisypayment');
    }

    protected function getShopId()
    {
        return $this->helper->getShopId();
    }

    protected function getInstalments()
    {
        return $this->helper->getInstalments();
    }

    protected function getZeroInterestRate()
    {
        return $this->helper->getZeroInterestRate() ? "true" : "false" ;
    }

    protected function getShopIdSimulation()
    {
        return $this->helper->getShopIdSimulation();
    }

    protected function isSimulationEnabled()
    {
        return $this->helper->isSimulationEnabled();
    }

    protected function getMinAmount()
    {
        return $this->helper->getMinAmount();
    }
}