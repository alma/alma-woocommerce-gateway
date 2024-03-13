<?php
/**
 * Alma_Payment_Gateway_Pay_Later.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/gateways/standard
 * @namespace Alma\Woocommerce\Gateways\Standard
 */

namespace Alma\Woocommerce\Gateways\Standard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Admin\Helpers\Alma_Check_Legal_Helper;
use Alma\Woocommerce\Alma_Payment_Upon_Trigger;
use Alma\Woocommerce\Gateways\Alma_Payment_Gateway;
use Alma\Woocommerce\Helpers\Alma_Encryptor_Helper;
use Alma\Woocommerce\Helpers\Alma_Payment_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Alma\Woocommerce\Helpers\Alma_Checkout_Helper;
use Alma\Woocommerce\Helpers\Alma_General_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Exceptions\Alma_No_Credentials_Exception;
use Alma\Woocommerce\Admin\Helpers\Alma_General_Helper as Alma_Admin_General_Helper;
use Alma\Woocommerce\Admin\Helpers\Alma_Form_Helper;
use Alma\Woocommerce\Exceptions\Alma_Api_Client_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Merchants_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Plans_Exception;

/**
 * Alma_Payment_Gateway_Pay_Later
 */
class Alma_Payment_Gateway_Pay_Later extends Alma_Payment_Gateway_Standard {

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return Alma_Constants_Helper::GATEWAY_ID_PAY_LATER;
	}

} // end \Alma_Pay_Gateway class
