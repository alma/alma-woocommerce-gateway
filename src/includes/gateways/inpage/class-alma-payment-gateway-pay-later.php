<?php
/**
 * Alma_Payment_Gateway_Pay_Later.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/gateways/inpage
 * @namespace Alma\Woocommerce\Gateways\InPage
 */

namespace Alma\Woocommerce\Gateways\Inpage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Gateways\Alma_Payment_Gateway;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Traits\Alma_In_Page_Gateway;

/**
 * Alma_Payment_Gateway_Pay_Later
 */
class Alma_Payment_Gateway_Pay_Later extends Alma_Payment_Gateway {

	use Alma_In_Page_Gateway;

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return Alma_Constants_Helper::GATEWAY_ID_IN_PAGE_PAY_LATER;
	}

} // end \Alma_Pay_Gateway class
