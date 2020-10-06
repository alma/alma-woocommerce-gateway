<?php
/**
 * Alma payments pluging for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' );
}

class Alma_WC_Product_Handler extends Alma_WC_Generic_Handler {
	const JQUERY_VARIABLE_PRODUCT_UPDATE_EVENT   = 'check_variations';
	const VARIABLE_PRODUCT_AMOUNT_QUERY_SELECTOR = 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi';

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
		$eligibility_msg             = '';
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

		$amount                = alma_wc_price_to_cents( wc_get_product()->get_price() );
		$amount_query_selector = null;
		$jquery_update_event   = null;
		$first_render          = true;

		$is_variable_product = wc_get_product()->get_type() === 'variable';

		if ( $is_variable_product ) {
			$amount_query_selector = 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi';
			$jquery_update_event   = self::JQUERY_VARIABLE_PRODUCT_UPDATE_EVENT;
			$first_render          = false;
		}

		$this->inject_payment_plan_html_js(
			$eligibility_msg,
			$skip_payment_plan_injection,
			$amount,
			$jquery_update_event,
			$amount_query_selector,
			$first_render
		);
	}
}
