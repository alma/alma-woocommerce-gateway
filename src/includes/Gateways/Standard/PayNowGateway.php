<?php
/**
 * PayNowGateway.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce//includes/Gateways/Standard
 * @namespace Alma\Woocommerce\Gateways\StandardGateway
 */

namespace Alma\Woocommerce\Gateways\Standard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Helpers\ConstantsHelper;

/**
 * StandardGateway
 */
class PayNowGateway extends StandardGateway {

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID_PAY_NOW;
	}

	/**
	 * Has fields.
	 *
	 * @return true
	 */
	public function has_fields() {
		return true;
	}


}
