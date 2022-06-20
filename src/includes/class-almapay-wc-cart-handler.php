<?php
/**
 * Alma cart handler
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Almapay_WC_Cart_Handler
 */
class Almapay_WC_Cart_Handler extends Almapay_WC_Generic_Handler {
	const JQUERY_CART_UPDATE_EVENT = 'updated_cart_totals';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		if ( 'yes' === almapay_wc_plugin()->settings->display_cart_eligibility ) {
			add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'display_cart_eligibility' ) );
		}
	}

	/**
	 *  Display message below cart totals to indicate whether Alma is available or not
	 */
	public function display_cart_eligibility() {
		$has_excluded_products = false;

		if (
				isset( almapay_wc_plugin()->settings->excluded_products_list ) &&
				is_array( almapay_wc_plugin()->settings->excluded_products_list ) &&
				count( almapay_wc_plugin()->settings->excluded_products_list ) > 0
		) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];

				if ( $this->is_product_excluded( $product_id ) ) {
					$has_excluded_products = true;
					break;
				}
			}
		}

		$cart   = new Almapay_WC_Model_Cart();
		$amount = $cart->get_total_in_cents();

		$this->inject_payment_plan_widget( $has_excluded_products, $amount, self::JQUERY_CART_UPDATE_EVENT );
	}

}
