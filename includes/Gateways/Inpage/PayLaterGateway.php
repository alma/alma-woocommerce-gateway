<?php
/**
 * PayLaterGateway.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Gateways/Inpage
 * @namespace Alma\Woocommerce\Gateways\InPage
 */

namespace Alma\Woocommerce\Gateways\Inpage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Gateways\AlmaPaymentGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Traits\InPageGatewayTrait;

/**
 * PayLaterGateway
 */
class PayLaterGateway extends AlmaPaymentGateway {

	const GATEWAY_ID = 'alma_in_page_pay_later';

	use InPageGatewayTrait;

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER;
	}
}
