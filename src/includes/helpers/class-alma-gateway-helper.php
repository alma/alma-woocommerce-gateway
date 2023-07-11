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

use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Exceptions\Alma_Exception;

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

			if (
				(
					Alma_Constants_Helper::GATEWAY_ID === $gateway->id
					|| Alma_Constants_Helper::GATEWAY_ID_IN_PAGE === $gateway->id
				)
				&& $has_excluded_products
			) {
				unset( $available_gateways[ $key ] );

				return $available_gateways;
			}

			$new_available_gateways[ $key ] = $gateway;
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
		if ( Alma_Constants_Helper::GATEWAY_ID === $id ) {
			return $this->alma_settings->get_title( Alma_Constants_Helper::GATEWAY_TITLE );
		}

		if ( Alma_Constants_Helper::GATEWAY_ID_IN_PAGE === $id ) {
			return $this->alma_settings->get_title( Alma_Constants_Helper::GATEWAY_TITLE_IN_PAGE );
		}

		return $title;
	}

	/**
	 * Filter the alma gateway title (visible on checkout page).
	 *
	 * @param string  $title The original title.
	 * @param integer $id The payment gateway id.
	 *
	 * @return string
	 */
	public function woocommerce_gateway_description( $title, $id ) {

		if ( Alma_Constants_Helper::GATEWAY_ID_IN_PAGE === $id ) {
			return $this->alma_settings->get_description( Alma_Constants_Helper::GATEWAY_DESCRIPTION_IN_PAGE );
		}

		return $title;
	}

	/**
	 * Get the title of the Alma Gateway.
	 *
	 * @param string $id The alma gateway type id.
	 * @return string
	 * @throws Alma_Exception Exception.
	 */
	public function get_alma_gateway_title( $id ) {

		if ( Alma_Constants_Helper::GATEWAY_ID === $id ) {
			return $this->alma_settings->get_title( Alma_Constants_Helper::PAYMENT_METHOD_PNX );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_LATER === $id ) {
			return $this->alma_settings->get_title( Alma_Constants_Helper::PAYMENT_METHOD_PAY_LATER );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_NOW === $id ) {
			return $this->alma_settings->get_title( Alma_Constants_Helper::PAYMENT_METHOD_PAY_NOW );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			return $this->alma_settings->get_title( Alma_Constants_Helper::PAYMENT_METHOD_PNX_PLUS_4 );
		}

		throw new Alma_Exception( sprintf( 'Unknown gateway id : %s', $id ) );
	}

	/**
	 * Get the title to replace the icon
	 *
	 * @param string $id The alma gateway type id.
	 * @return string
	 */
	public function get_alma_gateway_logo_text( $id ) {
		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_NOW === $id ) {
			return __( 'Pay Now', 'alma-gateway-for-woocommerce' );
		}

		return 'null';
	}

	/**
	 * Get the title of the Alma Gateway.
	 *
	 * @param string $id The alma gateway type id.
	 * @return string
	 * @throws Alma_Exception Exception.
	 */
	public function get_alma_gateway_description( $id ) {
		if ( Alma_Constants_Helper::GATEWAY_ID === $id ) {
			return $this->payment_helper->get_description( Alma_Constants_Helper::PAYMENT_METHOD_PNX );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_LATER === $id ) {
			return $this->payment_helper->get_description( Alma_Constants_Helper::PAYMENT_METHOD_PAY_LATER );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_NOW === $id ) {
			return $this->payment_helper->get_description( Alma_Constants_Helper::PAYMENT_METHOD_PAY_NOW );
		}

		if ( Alma_Constants_Helper::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			return $this->payment_helper->get_description( Alma_Constants_Helper::PAYMENT_METHOD_PNX_PLUS_4 );
		}

		throw new Alma_Exception( sprintf( 'Unknown gateway id : %s', $id ) );

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
