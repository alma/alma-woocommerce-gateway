<?php
/**
 * Alma_Gateway_Helper.
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
 * Alma_Gateway_Helper
 */
class Alma_Gateway_Helper {



	/**
	 * The settings.
	 *
	 * @var Alma_Settings_Helper
	 */
	protected $alma_settings;

	/**
	 * The payment helper.
	 *
	 * @var Alma_Payment_Helper
	 */
	protected $payment_helper;


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->alma_settings  = new Alma_Settings();
		$this->payment_helper = new Alma_Payment_Helper();
	}

	/**
	 * Filter available_gateways to add  "alma", "alma_pay_later" and "alma_pnx_plus_4".
	 *
	 * @param array $available_gateways The list of available gateways.
	 *
	 * @return array
	 */
	public function woocommerce_available_payment_gateways( $available_gateways ) {

		if ( is_admin() ) {
			return $available_gateways;
		}

		$product_helper = new Alma_Product_Helper();

		$has_excluded_products  = $product_helper->cart_has_excluded_product();
		$new_available_gateways = array();

		foreach ( $available_gateways as $key => $gateway ) {

			if ( 'alma' === $gateway->id ) {

				if ( $has_excluded_products ) {
					unset( $available_gateways[ $key ] );

					return $available_gateways;
				}

				$new_available_gateways = array_merge( $new_available_gateways, $this->alma_settings->build_new_available_gateways( $gateway ) );
			} else {
				$new_available_gateways[ $key ] = $gateway;
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

		if ( Alma_Constants_Helper::GATEWAY_ID !== substr( $id, 0, 4 ) ) {
			return $title;
		}

		if ( Alma_Constants_Helper::GATEWAY_ID === $id ) {
			$title = $this->alma_settings->get_title( Alma_Constants_Helper::PAYMENT_METHOD_PNX );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_LATER === $id ) {
			$title = $this->alma_settings->get_title( Alma_Constants_Helper::PAYMENT_METHOD_PAY_LATER );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			$title = $this->alma_settings->get_title( Alma_Constants_Helper::PAYMENT_METHOD_PNX_PLUS_4 );
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

		if ( Alma_Constants_Helper::GATEWAY_ID !== substr( $id, 0, 4 ) ) {
			return $description;
		}

		if ( Alma_Constants_Helper::GATEWAY_ID === $id ) {
			$description = $this->payment_helper->get_description( Alma_Constants_Helper::PAYMENT_METHOD_PNX );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_LATER === $id ) {
			$description = $this->payment_helper->get_description( Alma_Constants_Helper::PAYMENT_METHOD_PAY_LATER );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			$description = $this->payment_helper->get_description( Alma_Constants_Helper::PAYMENT_METHOD_PNX_PLUS_4 );
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
		if ( in_array( Alma_Constants_Helper::DEFAULT_FEE_PLAN, $plans, true ) ) {
			return Alma_Constants_Helper::DEFAULT_FEE_PLAN;
		}

		return array_shift( $plans );
	}
}