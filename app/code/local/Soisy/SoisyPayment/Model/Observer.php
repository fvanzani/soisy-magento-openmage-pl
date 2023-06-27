<?php
class Soisy_SoisyPayment_Model_Observer
{
    /**
     * Hides Soisy payment if amount is less than the minimum allowed value
     *
     * @param Varien_Event_Observer $observer
     */
    public function hideSoisyPayment(Varien_Event_Observer $observer) {
        $this->log("hideSoisyPayment: begin");
        try {

            try {
                $method = $observer->getEvent()->getMethodInstance()->getCode();
            } catch (Exception $e) {
                $method='';
            }

            $this->log("hideSoisyPayment: $method");
            if ($method == 'soisypayment') {
                $this->log("hideSoisyPayment: soisypayment is active, check order total...");
                $quote = $observer->getEvent()->getQuote();
                $grandTotal = (float)$quote->getgrandTotal();
                $minAmount = (float)Mage::helper('soisypayment')->getMinAmount();
                if ($grandTotal < $minAmount) {
                    $result = $observer->getEvent()->getResult();
                    $result->isAvailable = false;
                    $this->log("hideSoisyPayment: grandTotal ($grandTotal) < than minAmount ($minAmount), hide soisy payment!");
                } else {
                    $this->log("hideSoisyPayment: grandTotal ($grandTotal) >= minAmount ($minAmount), keep showing soisy payment");
                }
            }
        } catch (Exception $e) {
            $this->log("hideSoisyPayment: error");
            $this->log("hideSoisyPayment: ".$e->getMessage());
        }
        $this->log("hideSoisyPayment: end");
    }

    /**
     *  Handles Magento order creation event: creates Soisy order
     *
     * @param Varien_Event_Observer $observer
     */
    public function soisyOrderCreate(Varien_Event_Observer $observer)
	{
        $this->log( "soisyOrderCreate: begin" );
	    $event = $observer->getEvent();
        if ('sales_order_save_before' != $event->getName()) { return; }
        $this->log( "soisyOrderCreate: Event: ".$event->getName() );
        $helper=Mage::helper('soisypayment');
        if (!$helper->isEnabled()) {
            $this->log( "soisyOrderCreate: soisypayment module is not active, skip" );
        }
        if (Mage::registry('soisyOrderCreate')) {
            $this->log( "soisyOrderCreate: called twice, skip" );
            return;
        }
        Mage::register('soisyOrderCreate', true);

        // order cloned (to avoid conflict with nexi payment)
        $order = clone $event->getOrder();
        $order->load($order->getId());
        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if ($payment!=$helper->getPaymentCode()) {
            $this->log( "soisyOrderCreate: payment ($payment) is not ".($helper->getPaymentCode()).", skip" );
            return;
        }
        // from this point order not cloned
        $order = $event->getOrder();

        if ($order->getSoisyToken()) { // not implemented
            $this->log( "soisyOrderCreate: token already exists, skip" );
            return;
        }

        $incrementId=$order->getIncrementId();
        $amount=$order->getGrandTotal();
        $shopId=$helper->getShopId();

        $this->log("soisyOrderCreate: Order incrementId:$incrementId amount:$amount");

        $amount_cent=round($amount*100);
        $errorUrl = $helper->getErrorUrl();
        $successUrl = $helper->getSuccessUrl();
        $customerEmail= $order->getData('customer_email');
        $customerFirstname= $order->getData('customer_firstname');
        $customerLastname= $order->getData('customer_lastname');

        $postdata = [
            'firstname' => $customerFirstname,
            'lastname' => $customerLastname,
            'email'=> $customerEmail,
            'amount' => $amount_cent,
            'successUrl' => $successUrl,
            'errorUrl'=> $errorUrl,
            'orderReference' => $incrementId
        ];

        foreach($postdata as $k => $v) {
            $this->log("soisyOrderCreate: postdata $k: $v");
        }

        $token = $this->getSoisyToken($postdata);

        if ($token===false) {
            $this->log("soisyOrderCreate: token error, end");
            return;
        }
        $this->log("soisyOrderCreate: token: $token");

        $webAppUrl=$helper->getWebAppUrl($token);

        $this->log("soisyOrderCreate: webappUrl: $webAppUrl");

        $endpoint=$helper->getEndpoint();
        $orderUrl="{$endpoint}/api/shops/{$shopId}/orders/$token";
        $this->log("soisyOrderCreate: orderUrl: $orderUrl");

        // <b>Soisy order info:</b> <a target='_blank' href='$orderUrl' >$orderUrl</a>   <br>\n

        if ($token)
            $order->addStatusHistoryComment("
<b>PagoLight token:</b> $token <br>\n
<b>Customer webapp url:</b> <a target='_blank' href='$webAppUrl' >$webAppUrl</a>  ")
                ->setIsCustomerNotified(false)->setIsVisibleOnFront(false);

        if (!$token)
            $order->addStatusHistoryComment("<b>PagoLight token:</b> ERROR  ")
                ->setIsCustomerNotified(false)->setIsVisibleOnFront(false);

        $order->setSoisyToken($token);

        $this->log( "soisyOrderCreate: end" );
	}

    /**
     * Calls Soisy endpont to create Soisy order and returns Soisy token
     *
     * @param $postdata
     * @return false|mixed
     */
    protected function getSoisyToken($postdata) {
        $helper=Mage::helper('soisypayment');
        $authToken=$helper->getGeneralConfig('auth_token');
        $shopId=$helper->getGeneralConfig('shop_id');
        $endpoint=$helper->getEndpoint();
        $soisyUrl="{$endpoint}/api/shops/{$shopId}/orders";
        $this->log(" getSoisyToken:  shopId:$shopId - authToken:$authToken - soisyUrl:$soisyUrl");

        $postquery = http_build_query($postdata);
        //$this->log(print_r($postdata,true));
        $context=stream_context_create([
            'http'=>[ 'method'  =>'POST',
                'timeout' => 6.0,
                'header'  =>"X-Auth-Token: $authToken\r\nContent-Type: application/x-www-form-urlencoded",
                'content' => $postquery]
        ]);

        $result = @file_get_contents($soisyUrl,false,$context);
        $code=$this->getHttpCode($http_response_header);
        if ($code!="200") {
            $this->log(" getSoisyToken: error: response code is not 200 ($code), return false");
            $this->log(" getSoisyToken: response headers: ".print_r($http_response_header,true));
            $this->log(" getSoisyToken: result: ".print_r($result,true));
            return false;
        }
        $data=json_decode($result,true);
        if (!is_array($data)) {
            $this->log(" getSoisyToken: error: result is not json: ".print_r($result,true));
            return false;
        }
        if (empty($data['token'])) {
            $this->log(' getSoisyToken:  error: '.(!empty($data['errors'])?$data['errors']:""));
            return false;
        }
        $token = $data['token'];
        $this->log(" getSoisyToken: success: token = $token");
        return $token;
    }

    /**
     * Return http code from http response headers
     *
     * @param $http_response_header
     * @return int
     */
    protected function getHttpCode($http_response_header)
    {
        if(is_array($http_response_header))
        {
            $parts=explode(' ',$http_response_header[0]);
            if(count($parts)>1) //HTTP/1.0 <code> <text>
                return intval($parts[1]); //Get code
        }
        return 0;
    }

    /**
     * Sends to custom log
     *
     * @param $msg
     */
    protected function log($msg)
    {
        Mage::helper('soisypayment')->log($msg);
    }
}
