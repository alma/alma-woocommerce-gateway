<?php
/**
 * Alma_Product.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma_WC\Helpers
 */

namespace Alma_WC\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


use Alma_WC\Alma_Logger;
use Alma_WC\Alma_Settings;

/**
 * Class Alma_Checkout.
 */
class Alma_Product {


	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;


	/**
	 * Plugin settings
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger        = new Alma_Logger();
		$this->alma_settings = new Alma_Settings();
	}

	/**
	 * Check if you have excluded products in the cart.
	 *
	 * @return bool
	 */
	public function cart_has_excluded_product() {
		$has_excluded_products = false;

		if (
			wc()->cart === null
			|| ! $this->has_excluded_categories()
		) {
			return $has_excluded_products;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = $cart_item['product_id'];

			if ( $this->is_product_excluded( $product_id ) ) {
				$has_excluded_products = true;
				break;
			}
		}

		return $has_excluded_products;
	}

	/**
	 * Do we have excluded categories.
	 *
	 * @return bool
	 */
	public function has_excluded_categories() {
		if (
			isset( $this->alma_settings->excluded_products_list ) &&
			is_array( $this->alma_settings->excluded_products_list ) &&
			count( $this->alma_settings->excluded_products_list ) > 0
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a given product is excluded.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public function is_product_excluded( $product_id ) {
		foreach ( $this->alma_settings->excluded_products_list as $category_slug ) {
			if ( has_term( $category_slug, 'product_cat', $product_id ) ) {
				return true;
			}
		}

		return false;
	}
}
