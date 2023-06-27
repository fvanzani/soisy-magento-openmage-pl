<?php
class Soisy_SoisyPayment_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'soisypayment';

	protected $_formBlockType = 'soisypayment/form_soisypayment';
	//protected $_infoBlockType = 'soisypayment/info_soisypayment';
	protected $_canUseForMultishipping  = false;
	protected $_canUseInternal          = false;
}
?>
