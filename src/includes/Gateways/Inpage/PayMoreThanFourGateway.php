<?php
/**
 * PayMoreThanFourGateway.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce//includes/Gateways/Standard
 * @namespace Alma\Woocommerce\Gateways\StandardGateway
 */

namespace Alma\Woocommerce\Gateways\Inpage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Gateways\AlmaPaymentGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Traits\InPageGatewayTrait;

/**
 * PayMoreThanFourGateway
 */
class PayMoreThanFourGateway extends AlmaPaymentGateway {

	use InPageGatewayTrait;

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR;
	}

}
