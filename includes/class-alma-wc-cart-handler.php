<?php
/**
 * Alma payments pluging for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' );
}

class Alma_WC_Cart_Handler extends Alma_WC_Generic_Handler {
	public function __construct() {
		parent::__construct();

		if ( 'yes' === alma_wc_plugin()->settings->display_cart_eligibility ) {
			add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'display_cart_eligibility' ) );
		}
	}

	/**
	 *  Display message below cart totals to indicate whether Alma is available or not
	 */
	public function display_cart_eligibility() {
		$eligibility_msg             = alma_wc_plugin()->settings->cart_is_eligible_message;
		$skip_payment_plan_injection = false;

		if (
				isset( alma_wc_plugin()->settings->excluded_products_list ) &&
				is_array( alma_wc_plugin()->settings->excluded_products_list ) &&
				count( alma_wc_plugin()->settings->excluded_products_list ) > 0
		) {
			foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
				$product = $cart_item['data'];

				foreach ( alma_wc_plugin()->settings->excluded_products_list as $category_slug ) {
					if ( has_term( $category_slug, 'product_cat', $product->get_id() ) ) {
						$skip_payment_plan_injection = true;
						$eligibility_msg             = alma_wc_plugin()->settings->cart_not_eligible_message_gift_cards;
					}
				}
			}
		}

		$cart   = new Alma_WC_Cart();
		$amount = $cart->get_total();

		if ( ! count( alma_wc_get_eligible_installments_according_to_settings( $amount ) ) ) {
			$skip_payment_plan_injection = true;
			$eligibility_msg             = alma_wc_plugin()->settings->cart_not_eligible_message;
		}

		$this->inject_payment_plan_html_js( $eligibility_msg, $skip_payment_plan_injection, $amount );
	}
}
