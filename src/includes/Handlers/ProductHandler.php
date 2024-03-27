<?php
/**
 * ProductHandler.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Handlers
 * @namespace Alma\Woocommerce\Handlers
 */

namespace Alma\Woocommerce\Handlers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * ProductHandler
 */
class ProductHandler extends GenericHandler {


	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		if ( 'yes' === $this->alma_settings->display_product_eligibility ) {
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
			$this->logger->warning( 'Product not found: product badge injection failed.' );
			return;
		}

		if (
			isset( $this->alma_settings->excluded_products_list ) &&
			is_array( $this->alma_settings->excluded_products_list ) &&
			count( $this->alma_settings->excluded_products_list ) > 0
		) {
			$product_id = $product->get_id();

			if ( $this->is_product_excluded( $product_id ) ) {
				$has_excluded_products = true;
			}
		}

		if ( ! $product->is_in_stock() ) {
			$this->logger->warning(
				'Product not in stock: product badge injection failed.',
				array( 'ProductId' => $product->get_id() )
			);
			return;
		}

		$price = $this->get_price_to_inject_in_widget( $product );

		if ( ! $price ) {
			// translators: %s: the product price.
			$this->logger->info(
				'Product price unknown, product badge injection failed.',
				array(
					'ProductId' => $product->get_id(),
				)
			);

			return;
		}
		$amount_query_selector            = null;
		$amount_sale_price_query_selector = null;
		$jquery_update_event              = null;

		$is_variable_product = $product->get_type() === 'variable';

		if ( $is_variable_product ) {
			$jquery_update_event              = $this->alma_settings->variable_product_check_variations_event;
			$amount_query_selector            = $this->alma_settings->variable_product_price_query_selector;
			$amount_sale_price_query_selector = $this->alma_settings->variable_product_sale_price_query_selector;
		}

		$this->inject_payment_plan_widget(
			$has_excluded_products,
			$this->helper_tools->alma_price_to_cents( $price ),
			$jquery_update_event,
			$amount_query_selector,
			$amount_sale_price_query_selector
		);
	}

	/**
	 * Returns the product price to send to Alma's API to display the widget.
	 *
	 * @param \WC_Product $product A WC product.
	 * @return integer.
	 */
	private function get_price_to_inject_in_widget( $product ) {
		$price = wc_get_price_including_tax( $product );

		if (
			$product->is_type( 'variable' )
			&& $product instanceof \WC_Product_Variable
		) {
			$price = $product->get_variation_regular_price( 'min', true );

			if ( $product->is_on_sale() ) {
				$price = $product->get_variation_sale_price( 'min', true );
			}
		}

		return $price;
	}
}
