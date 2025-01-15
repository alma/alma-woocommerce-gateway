<?php
/**
 * InPageGatewayTrait.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/traits
 * @namespace Alma\Woocommerce\Traits
 */

namespace Alma\Woocommerce\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * InPageGatewayTrait
 */
trait InPageGatewayTrait {

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
		$eligibilities  = $this->cart_helper->get_cart_eligibilities();
		$eligible_plans = $this->cart_helper->get_eligible_plans_keys_for_cart( $eligibilities );
		$eligible_plans = $this->alma_plan_helper->order_plans( $eligible_plans );

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans[ $this->id ] );

		$this->alma_plan_helper->render_checkout_fields( $eligibilities, $eligible_plans, $this->id, $default_plan );

		$alma_args = array(
			'merchant_id'     => $this->alma_settings->get_active_merchant_id(),
			'amount_in_cents' => $this->cart_helper->get_total_in_cents(),
			'environment'     => strtoupper( $this->alma_settings->get_environment() ),
			'locale'          => strtoupper( substr( get_locale(), 0, 2 ) ),
		);

		wp_localize_script( 'alma-checkout-in-page', 'alma_iframe_params', $alma_args );
		wp_localize_script( 'alma-checkout-in-page', 'alma_iframe_paiement', array() );
	}


}
