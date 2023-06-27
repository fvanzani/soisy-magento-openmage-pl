<?php
class Soisy_SoisyPayment_Helper_Data extends Mage_Core_Helper_Abstract
{
    const DEFAULT_MIN_AMOUNT=250;
    const FRONTEND_URL='soisy'; // config.xml -> frontName
    const PAYMENT_CODE='soisypayment';
    const SIMULATION_INSTALMENTS=12;
    const SIMULATION_ZEROINTERESTRATE=false;

    public function isEnabled()
    {
        return (boolean) Mage::getStoreConfig('payment/soisypayment/active');
    }

    public function getMinAmount()
    {
        $min = abs(Mage::getStoreConfig('payment/soisypayment/min_amount'));
        return $min>=0 ? $min : self::DEFAULT_MIN_AMOUNT;
    }

    public function isLogEnabled()
    {
        return (boolean) Mage::getStoreConfig('payment/soisypayment/log_enabled');
    }

    public function isSimulationEnabled()
    {
        return (boolean) Mage::getStoreConfig('payment/soisypayment/simulation_enabled');
    }

    public function log($msg)
    {
        if (!$this->isLogEnabled()) {
            return;
        }
        Mage::log($msg,null,'soisypayment.log');
    }

    public function getGeneralConfig($path)
    {
        return trim(Mage::getStoreConfig('payment/soisypayment/'.trim(trim($path),'/')));
    }

    public function getEndpoint() {
        return rtrim(Mage::getStoreConfig('payment/soisypayment/endpoint'),'/');
    }

    public function getWebApp() {
        return rtrim(Mage::getStoreConfig('payment/soisypayment/webapp'),'/');
    }

    public function getWebAppUrl($token='')
    {
        if(!$token)
            $token=$this->getSoisyToken();
        if (!$token) {
            return false;
        }
        $webApp=$this->getWebApp();
        $shopId=$this->getShopId();
        $webAppUrl="{$webApp}/{$shopId}#/loan-request?token={$token}";
        return $webAppUrl;
    }

    public function getSoisyToken()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $this->log(" getSoisyToken: orderId=$orderId");
        $order = Mage::getModel("sales/order")->loadByIncrementId($orderId);

        $soisyToken=$order->getSoisyToken();
        $this->log(" getSoisyToken: soisyToken=$soisyToken");
        return $soisyToken;
        //return Mage::getSingleton('core/session')->getSoisyToken();
    }

    public function getFrontendRelUrl()
    {
        return self::FRONTEND_URL;
    }

    public function getSuccessUrl()
    {
        $rel=$this->getFrontendRelUrl();
        return Mage::getUrl($rel, array('_secure' => false));
    }

    public function getErrorUrl()
    {
        $rel=$this->getFrontendRelUrl();
        return Mage::getUrl($rel, array('_secure' => false, '_query' =>'error'));
    }

    public function getShopId()
    {
        return $this->getGeneralConfig('shop_id');
    }

    public function getShopIdSimulation()
    {
        if ($this->isSandbox()) {
            return $this->getGeneralConfig('shop_id_simulation');
        }
        return $this->getShopId();
    }

    public function isSandbox()
    {
        if (strpos($this->getEndpoint(), 'sandbox') !== false) {
            return true;
        }
        return false;
    }

    public function getZeroInterestRate()
    {
        return self::SIMULATION_ZEROINTERESTRATE;
    }

    public function getInstalments()
    {
        return self::SIMULATION_INSTALMENTS;
    }

    public function getPaymentCode()
    {
        return self::PAYMENT_CODE;
    }


}