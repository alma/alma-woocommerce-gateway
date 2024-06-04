<?php
/**
 * StandardGateway.
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

use Alma\Woocommerce\Gateways\AlmaPaymentGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;

/**
 * StandardGateway
 */
class StandardGateway extends AlmaPaymentGateway {

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID;
	}

	/**
	 *  Custom payment fields for a payment gateway.
	 *  (We have three payment gateways : "alma", "alma_pay_later", and "alma_pnx_plus_4")
	 *
	 * @return void
	 * @throws \Alma\Woocommerce\Exceptions\AlmaException The alma Exception.
	 */
	public function payment_fields() {
		$this->checkout_helper->render_nonce_field( $this->id );

		// We get the eligibilites.
		$eligibilities  = $this->cart_helper->get_cart_eligibilities();
		$eligible_plans = $this->cart_helper->get_eligible_plans_keys_for_cart( $eligibilities );
		$eligible_plans = $this->alma_plan_helper->order_plans( $eligible_plans );

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans );

		$this->alma_plan_helper->render_checkout_fields( $eligibilities, $eligible_plans, $this->id, $default_plan );
	}

}
