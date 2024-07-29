<?php
/**
 * CartHandler.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Handlers
 * @namespace Alma\Woocommerce\Handlers
 */

namespace Alma\Woocommerce\Handlers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Builders\Helpers\CartHelperBuilder;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Helpers\ConstantsHelper;

/**
 * CartHandler
 */
class CartHandler extends GenericHandler {

	/**
	 * The cart factory.
	 *
	 * @var CartFactory
	 */
	protected $cart_factory;



	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->cart_factory = new CartFactory();

		if ( 'yes' === $this->alma_settings->display_cart_eligibility ) {
			add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'display_cart_eligibility' ) );
		}
	}

	/**
	 *  Display message below cart totals to indicate whether Alma is available or not
	 */
	public function display_cart_eligibility() {
		$has_excluded_products = false;

		if (
			isset( $this->alma_settings->excluded_products_list ) &&
			is_array( $this->alma_settings->excluded_products_list ) &&
			count( $this->alma_settings->excluded_products_list ) > 0
		) {
			$cart_items = $this->cart_factory->get_cart_items();

			foreach ( $cart_items as $cart_item ) {
				$product_id = $cart_item['product_id'];

				if ( $this->is_product_excluded( $product_id ) ) {
					$has_excluded_products = true;
					break;
				}
			}
		}

		$cart_helper_builder = new CartHelperBuilder();
		$cart_helper         = $cart_helper_builder->get_instance();

		$amount = $cart_helper->get_total_in_cents();

		$this->inject_payment_plan_widget( $has_excluded_products, $amount, ConstantsHelper::JQUERY_CART_UPDATE_EVENT );
	}

}
