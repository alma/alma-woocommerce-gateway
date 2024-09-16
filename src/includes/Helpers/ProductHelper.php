<?php
/**
 * ProductHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\CoreFactory;

/**
 * Class CheckoutHelper.
 */
class ProductHelper {


	/**
	 * The logger.
	 *
	 * @var AlmaLogger
	 */
	protected $logger;


	/**
	 * Plugin settings
	 *
	 * @var SettingsHelper
	 */
	protected $alma_settings;

	/**
	 * The cart factory.
	 *
	 * @var CartFactory
	 */
	protected $cart_factory;

	/**
	 * The core factory.
	 *
	 * @var CoreFactory
	 */
	protected $core_factory;


	/**
	 *
	 * Construct.
	 *
	 * @param AlmaLogger   $alma_logger The alma logger.
	 * @param AlmaSettings $alma_settings The alma settings.
	 * @param CartFactory  $cart_factory    The cart factory.
	 * @param CoreFactory  $core_factory The core factory.
	 */
	public function __construct( $alma_logger, $alma_settings, $cart_factory, $core_factory ) {
		$this->logger        = $alma_logger;
		$this->alma_settings = $alma_settings;
		$this->cart_factory  = $cart_factory;
		$this->core_factory  = $core_factory;

	}

	/**
	 * Check if you have excluded products in the cart.
	 *
	 * @return bool
	 */
	public function cart_has_excluded_product() {
		$has_excluded_products = false;

		if (
			$this->cart_factory->get_cart() === null
			|| ! $this->has_excluded_categories()
		) {
			return $has_excluded_products;
		}

		$cart_items = $this->cart_factory->get_cart_items();
		foreach ( $cart_items as $cart_item ) {
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
			if ( $this->core_factory->has_term( $category_slug, 'product_cat', $product_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return attachment url or empty string
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function get_attachment_url( $attachment_id = 0 ) {
		return wp_get_attachment_url( $attachment_id ) ? wp_get_attachment_url( $attachment_id ) : '';
	}
}
