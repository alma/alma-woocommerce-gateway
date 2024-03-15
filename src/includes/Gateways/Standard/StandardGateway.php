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

use Alma\Woocommerce\Exceptions\NoCredentialsException;
use Alma\Woocommerce\Gateways\AlmaPaymentGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;

/**
 * StandardGateway
 */
class StandardGateway extends AlmaPaymentGateway {


	/**
	 * Constructor.
	 *
	 * @param boolean $check_basics Basic checks.
	 *
	 * @throws NoCredentialsException The exception.
	 */
	public function __construct( $check_basics = true ) {
		$this->id                 = $this->get_gateway_id();
		$this->has_fields         = $this->has_fields();
		$this->method_title       = __( 'Payment in instalments and deferred with Alma - 1x 2x 3x 4x, D+15 or D+30', 'alma-gateway-for-woocommerce' );
		$this->method_description = __( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.', 'alma-gateway-for-woocommerce' );
		parent::__construct( $check_basics );
	}

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID;
	}

	/**
	 * Has fields.
	 *
	 * @return true
	 */
	public function has_fields() {
		return true;
	}

	/**
	 * Custom payment fields for a payment gateway.
	 * (We have three payment gateways : "alma", "alma_pay_later", and "alma_pnx_plus_4")
	 */
	public function payment_fields() {
		$this->checkout_helper->render_nonce_field( $this->id );

		// We get the eligibilites.
		$eligibilities  = $this->alma_settings->get_cart_eligibilities();
		$eligible_plans = $this->alma_settings->get_eligible_plans_keys_for_cart( $eligibilities );

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans );

		$this->plan_builder->render_checkout_fields( $eligibilities, $eligible_plans, $this->id, $default_plan );
	}

}
