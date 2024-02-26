<?php
/**
 * Alma_Payment_Gateway_Pay_Now.
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

use Alma\Woocommerce\Exceptions\Alma_No_Credentials_Exception;
use Alma\Woocommerce\Gateways\Alma_Payment_Gateway;
use Alma\Woocommerce\Helpers\Alma_Cart_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

/**
 * Alma_Payment_Gateway_Pay_Now
 */
class Alma_Payment_Gateway_Pay_Now extends Alma_Payment_Gateway {

	/**
	 * The cart helper.
	 *
	 * @var Alma_Cart_Helper
	 */
	protected $cart_helper;

	/**
	 * Constructor.
	 *
	 * @param boolean $check_basics Basic checks.
	 *
	 * @throws Alma_No_Credentials_Exception The exception.
	 */
	public function __construct( $check_basics = true ) {
		$this->id                 = $this->get_gateway_id();
		$this->has_fields         = $this->has_fields();
		$this->cart_helper        = new Alma_Cart_Helper();
		$this->method_title       = __( 'Payment in instalments and deferred with Alma - 2x 3x 4x', 'alma-gateway-for-woocommerce' );
		$this->method_description = __( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.', 'alma-gateway-for-woocommerce' );
		parent::__construct( $check_basics );
	}

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return Alma_Constants_Helper::GATEWAY_ID_IN_PAGE_PAY_NOW;
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
	 * Check if the gateway is available for use.
	 *
	 * @return bool Is available.
	 */
	public function is_available() {

		if (
			! empty( $this->alma_settings->settings['display_in_page'] )
			&& 'no' === $this->alma_settings->settings['display_in_page']
		) {
			return false;
		}

		return parent::is_available();
	}
	/**
	 * Custom payment fields for a payment gateway.
	 * (We have three payment gateways : "alma", "alma_pay_later", and "alma_pnx_plus_4")
	 */
	public function payment_fields() {
		$this->checkout_helper->render_nonce_field( $this->id );

		// We get the eligibilites.
		$eligibilities = $this->alma_settings->get_cart_eligibilities();

		$eligible_plans = $this->alma_settings->get_eligible_plans_keys_for_cart( $eligibilities );

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans[ $this->id ] );

		$this->plan_builder->render_checkout_fields( $eligibilities, $eligible_plans, $this->id, $default_plan );

		$alma_args = array(
			'merchant_id'     => $this->alma_settings->get_active_merchant_id(),
			'amount_in_cents' => $this->cart_helper->get_total_in_cents(),
			'environment'     => strtoupper( $this->alma_settings->get_environment() ),
			'locale'          => strtoupper( substr( get_locale(), 0, 2 ) ),
		);

		wp_localize_script( 'alma-checkout-in-page', 'alma_iframe_params', $alma_args );
	}

	/**
	 * Validate fields.
	 *
	 * @return true
	 */
	public function validate_fields() {
		return true;
	}
} // end \Alma_Pay_Gateway class
