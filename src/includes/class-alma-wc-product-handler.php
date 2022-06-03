<?php
/**
 * Alma product handler
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Product_Handler
 */
class Alma_WC_Product_Handler extends Alma_WC_Generic_Handler {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		if ( 'yes' === alma_wc_plugin()->settings->display_product_eligibility ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'inject_payment_plan' ), 29 );
		}
	}

	/**
	 * Display payment plan below the 'add to cart' button to indicate whether Alma is available or not
	 *
	 * @param mixed $the_product Post object or post ID of the product.
	 */
	public function inject_payment_plan( $the_product = false ) {
		$has_excluded_products = false;

		$product = ( $the_product ) ? wc_get_product( $the_product ) : wc_get_product();
		if ( ! $product ) {
			$this->logger->info( __( 'Product not found: product badge injection failed.', 'alma-gateway-for-woocommerce' ) );
			return;
		}
		if (
				isset( alma_wc_plugin()->settings->excluded_products_list ) &&
				is_array( alma_wc_plugin()->settings->excluded_products_list ) &&
				count( alma_wc_plugin()->settings->excluded_products_list ) > 0
		) {
			$product_id = $product->get_id();

			if ( $this->is_product_excluded( $product_id ) ) {
				$has_excluded_products = true;
			}
		}

		if ( ! $product->is_in_stock() ) {
			$this->logger->info( __( 'Product not in stock: product badge injection failed.', 'alma-gateway-for-woocommerce' ) );
			return;
		}

		$price = $this->get_price_to_inject_in_widget( $product );

		if ( ! $price ) {
			// translators: %s: the product price.
			$this->logger->info( sprintf( __( 'Product price (%s): product badge injection failed.', 'alma-gateway-for-woocommerce' ), $price ) );
			return;
		}
		$amount_query_selector            = null;
		$amount_sale_price_query_selector = null;
		$jquery_update_event              = null;

		$is_variable_product = $product->get_type() === 'variable';

		if ( $is_variable_product ) {
			$jquery_update_event              = alma_wc_plugin()->settings->variable_product_check_variations_event;
			$amount_query_selector            = alma_wc_plugin()->settings->variable_product_price_query_selector;
			$amount_sale_price_query_selector = alma_wc_plugin()->settings->variable_product_sale_price_query_selector;
		}

		$this->inject_payment_plan_widget(
			$has_excluded_products,
			alma_wc_price_to_cents( $price ),
			$jquery_update_event,
			$amount_query_selector,
			$amount_sale_price_query_selector
		);
	}

	/**
	 * Returns the product price to send to Alma's API to display the widget.
	 *
	 * @param WC_Product $product A WC product.
	 * @return integer.
	 */
	private function get_price_to_inject_in_widget( $product ) {

		if ( version_compare( wc()->version, '3.0', '>=' ) ) {
			$price = wc_get_price_including_tax( $product );
		} else {
			$price = $product->get_price_including_tax();
		}

		if ( $product->is_type( 'variable' ) && $product instanceof WC_Product_Variable ) {
			$price = $product->get_variation_regular_price( 'min', true );
			if ( $product->is_on_sale() ) {
				$price = $product->get_variation_sale_price( 'min', true );
			}
		}

		return $price;
	}

}
