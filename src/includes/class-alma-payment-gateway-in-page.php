<?php
/**
 * Alma_Payment_Gateway_In_Page.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Helpers\Alma_Cart_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

/**
 * Alma_Payment_Gateway_In_Page
 */
class Alma_Payment_Gateway_In_Page extends Alma_Payment_Gateway {

	/**
	 * @var Alma_Cart_Helper
	 */
	protected $cart_helper;

	public function __construct( $check_basics = true ) {
		$this->id         = Alma_Constants_Helper::GATEWAY_ID_IN_PAGE;
		$this->has_fields = false;
		$this->cart_helper = new Alma_Cart_Helper();
		parent::__construct( $check_basics );
	}

	/**
	 * Add filters.
	 *
	 * @return void
	 */
	public function add_filters() {
		add_filter(
			'woocommerce_available_payment_gateways',
			array(
				$this->gateway_helper,
				'woocommerce_available_payment_gateways',
			)
		);

		add_filter( 'woocommerce_gateway_title', array( $this->gateway_helper, 'woocommerce_gateway_title' ), 10, 2 );
		add_filter( 'woocommerce_gateway_description', array( $this->gateway_helper, 'woocommerce_gateway_description' ), 10, 2 );
		add_filter( 'allowed_redirect_hosts', array( $this->general_helper, 'alma_domains_whitelist' ) );

	}



	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 */
	public function get_icon() {
		return Alma_Assets_Helper::get_icon( $this->get_title(), $this->id, Alma_Constants_Helper::ALMA_SHORT_LOGO_PATH );
	}


	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool Is available.
	 */
	public function is_available() {

		if (
			! empty( $this->alma_settings->settings['display_in_page'] )
			&& $this->alma_settings->settings['display_in_page'] == 'no'
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
		$eligibilities  = $this->alma_settings->get_cart_eligibilities();
		$eligible_plans = $this->alma_settings->get_eligible_plans_keys_for_cart( $eligibilities );
		foreach ( $eligible_plans as $key => $plan ) {
			if (
				! in_array(
					$plan,
					array( 'general_2_0_0', 'general_3_0_0', 'general_4_0_0' )
				) ) {
				unset( $eligible_plans[ $key ] );
			}
		}

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans );

		$this->plan_builder->render_checkout_fields( $eligibilities, $eligible_plans, $this->id, $default_plan );

		$alma_args = array(
			'merchant_id' => $this->alma_settings->get_active_merchant_id(),
			'amount_in_cents' => $this->cart_helper->get_total_in_cents()
		);
		wp_localize_script( 'alma-checkout-in-page', 'alma_iframe_params', $alma_args );
		wp_localize_script( 'alma-checkout-in-page', 'alma_iframe_paiement', array() );
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array The result.
	 */
	public function process_payment( $order_id ) {
	}


} // end \Alma_Pay_Gateway class
