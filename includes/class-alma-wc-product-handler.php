<?php
/**
 * Alma payments pluging for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' );
}

class Alma_WC_Product_Handler extends Alma_WC_Generic_Handler {
	public function __construct() {
		parent::__construct();

		if ( 'yes' === alma_wc_plugin()->settings->display_product_eligibility ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'inject_payment_plan' ), 29 );
		}
	}

	/**
	 *  Display payment plan below the 'add to cart' button to indicate whether Alma is available or not
	 */
	public function inject_payment_plan() {
		$eligibility_msg             = alma_wc_plugin()->settings->product_is_eligible_message;
		$skip_payment_plan_injection = false;

		if (
				isset( alma_wc_plugin()->settings->excluded_products_list ) &&
				is_array( alma_wc_plugin()->settings->excluded_products_list ) &&
				count( alma_wc_plugin()->settings->excluded_products_list ) > 0
		) {
			$product_id = wc_get_product()->get_id();

			foreach ( alma_wc_plugin()->settings->excluded_products_list as $category_slug ) {
				if ( has_term( $category_slug, 'product_cat', $product_id ) ) {
					$skip_payment_plan_injection = true;
					$eligibility_msg             = alma_wc_plugin()->settings->product_not_eligible_message;
				}
			}
		}

		$amount = alma_wc_price_to_cents( wc_get_product()->get_price() );

		$is_variable_product = wc_get_product()->get_type() === 'variable';

		if ( $is_variable_product ) {
			$eligibility_msg = '';
		} elseif ( ! count( alma_wc_get_eligible_installments_according_to_settings( $amount ) ) ) {
			$eligibility_msg = alma_wc_plugin()->settings->product_not_eligible_message;
		}

		$this->inject_payment_plan_html_js( $eligibility_msg, $skip_payment_plan_injection, $amount );
	}
}
