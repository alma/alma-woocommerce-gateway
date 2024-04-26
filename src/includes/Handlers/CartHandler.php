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

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\CurrencyHelper;
use Alma\Woocommerce\Helpers\PriceHelper;
use Alma\Woocommerce\Helpers\SessionHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Helpers\VersionHelper;

/**
 * CartHandler
 */
class CartHandler extends GenericHandler {



	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

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
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];

				if ( $this->is_product_excluded( $product_id ) ) {
					$has_excluded_products = true;
					break;
				}
			}
		}

		$cart_helper = new CartHelper(
			new ToolsHelper(
				new AlmaLogger(),
				new PriceHelper(),
				new CurrencyHelper()
			),
			new SessionHelper(),
			new VersionHelper()
		);
		$amount      = $cart_helper->get_total_in_cents();

		$this->inject_payment_plan_widget( $has_excluded_products, $amount, ConstantsHelper::JQUERY_CART_UPDATE_EVENT );
	}

}
