<?php
class Soisy_SoisyPayment_Block_Redirect extends Mage_Payment_Block_Form
{
    protected $helper;

    public function __construct()
    {
        $this->helper=Mage::helper('soisypayment');

    }

    protected function getDescription()
    {
        if (!$this->helper->getSoisyToken())
            return $this->helper->getGeneralConfig('error_description_before_redirect');
        return $this->helper->getGeneralConfig('success_description_before_redirect');
    }

    protected function getSoisyToken()
    {
        return $this->helper->getSoisyToken();
    }

    protected function getWebAppUrl()
    {
        return $this->helper->getWebAppUrl();
    }

    protected function isSoisyPayment()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel("sales/order")->loadByIncrementId($orderId);
        $paymentCode = $order->getPayment()->getMethod();
        return $paymentCode==$this->helper->getPaymentCode();
    }
}