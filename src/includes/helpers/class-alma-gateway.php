<?php
/**
 * Alma_Gateway.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Alma_Settings;
/**
 * Alma_Gateway
 */
class Alma_Gateway {



	/**
	 * The settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;

	/**
	 * The payment helper.
	 *
	 * @var Alma_Payment
	 */
	protected $payment_helper;


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->alma_settings  = new Alma_Settings();
		$this->payment_helper = new Alma_Payment();
	}

	/**
	 * Filter available_gateways to add "alma_pay_later" and "alma_pnx_plus_4".
	 *
	 * @param array $available_gateways The list of available gateways.
	 *
	 * @return array
	 */
	public function woocommerce_available_payment_gateways( $available_gateways ) {

		if ( is_admin() ) {
			return $available_gateways;
		}

		$product_helper = new Alma_Product();

		if ( $product_helper->cart_has_excluded_product() ) {
			return array();
		}

		$new_available_gateways = array();
		foreach ( $available_gateways as $key => $gateway ) {
			$new_available_gateways[ $key ] = $gateway;

			if ( 'alma' === $gateway->id ) {
				$new_available_gateways = array_merge( $new_available_gateways, $this->alma_settings->build_new_available_gateways( $gateway ) );
			}
		}

		return $new_available_gateways;
	}

	/**
	 * Filter the alma gateway title (visible on checkout page).
	 *
	 * @param string  $title The original title.
	 * @param integer $id The payment gateway id.
	 *
	 * @return string
	 */
	public function woocommerce_gateway_title( $title, $id ) {

		if ( Alma_Constants::GATEWAY_ID !== substr( $id, 0, 4 ) ) {
			return $title;
		}

		if ( Alma_Constants::GATEWAY_ID === $id ) {
			$title = $this->alma_settings->get_title( Alma_Constants::PAYMENT_METHOD_PNX );
		}

		if ( Alma_Constants::ALMA_GATEWAY_PAY_LATER === $id ) {
			$title = $this->alma_settings->get_title( Alma_Constants::PAYMENT_METHOD_PAY_LATER );
		}

		if ( Alma_Constants::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			$title = $this->alma_settings->get_title( Alma_Constants::PAYMENT_METHOD_PNX_PLUS_4 );
		}

		return $title;
	}

	/**
	 * Filter the alma gateway description (visible on checkout page).
	 *
	 * @param string  $description The original description.
	 * @param integer $id The payment gateway id.
	 *
	 * @return string
	 */
	public function woocommerce_gateway_description( $description, $id ) {

		if ( Alma_Constants::GATEWAY_ID !== substr( $id, 0, 4 ) ) {
			return $description;
		}

		if ( Alma_Constants::GATEWAY_ID === $id ) {
			$description = $this->payment_helper->get_description( Alma_Constants::PAYMENT_METHOD_PNX );
		}

		if ( Alma_Constants::ALMA_GATEWAY_PAY_LATER === $id ) {
			$description = $this->payment_helper->get_description( Alma_Constants::PAYMENT_METHOD_PAY_LATER );
		}

		if ( Alma_Constants::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			$description = $this->payment_helper->get_description( Alma_Constants::PAYMENT_METHOD_PNX_PLUS_4 );
		}

		return $description;
	}

	/**
	 * Check if cart has eligibilities.
	 *
	 * @return bool
	 */
	public function is_there_eligibility_in_cart() {
		return count( $this->alma_settings->get_eligible_plans_keys_for_cart() ) > 0;
	}

	/**
	 * Check if there is some excluded products into cart
	 *
	 * @return bool
	 */
	public function cart_contains_excluded_category() {
		if ( wc()->cart === null ) {
			return false;
		}
		if (
			property_exists( $this->alma_settings, 'excluded_products_list' )
			&& is_array( $this->alma_settings->excluded_products_list )
			&& count( $this->alma_settings->excluded_products_list ) > 0
		) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];

				foreach ( $this->alma_settings->excluded_products_list as $category_slug ) {
					if ( has_term( $category_slug, 'product_cat', $product_id ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Check if cart eligibilities has at least one eligible plan.
	 *
	 * @return bool
	 */
	public function is_cart_eligible() {
		$logger = new Alma_Logger();
		$logger->info( 'is_cart_eligible' );
		$eligibilities = $this->alma_settings->get_cart_eligibilities();

		if ( ! $eligibilities ) {
			return false;
		}

		$is_eligible = false;

		foreach ( $eligibilities as $plan ) {
			$is_eligible = $is_eligible || $plan->isEligible; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		return $is_eligible;
	}

	/**
	 * Gets default plan according to eligible pnx list.
	 *
	 * @param string[] $plans the list of eligible pnx.
	 *
	 * @return string|null
	 */
	public function get_default_plan( $plans ) {
		if ( ! count( $plans ) ) {
			return null;
		}
		if ( in_array( Alma_Constants::DEFAULT_FEE_PLAN, $plans, true ) ) {
			return Alma_Constants::DEFAULT_FEE_PLAN;
		}

		return end( $plans );
	}
}
