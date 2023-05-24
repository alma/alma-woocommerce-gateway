<?php
/**
 * Alma_Cart.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/models
 * @namespace Alma\Woocommerce\Models
 */

namespace Alma\Woocommerce\Models;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Helpers\Alma_Tools_Helper;

/**
 * Alma_Cart
 */
class Alma_Cart {

	/**
	 * Cart
	 *
	 * @var \WC_Cart|null
	 */
	protected $cart;

	/**
	 * Helper global.
	 *
	 * @var Alma_Tools_Helper
	 */
	protected $tool_helper;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->cart        = WC()->cart;
		$this->tool_helper = new Alma_Tools_Helper();
	}

	/**
	 * Get cart total in cents.
	 *
	 * @return integer
	 * @see alma_price_to_cents()
	 * @see get_total_from_cart
	 */
	public function get_total_in_cents() {
		return $this->tool_helper->alma_price_to_cents( $this->get_total_from_cart() );
	}

	/**
	 * Gets total from wc cart depending on which wc version is running.
	 *
	 * @return float
	 */
	protected function get_total_from_cart() {
		if ( ! $this->cart ) {
			return 0;
		}

		if ( version_compare( WC()->version, '3.2.0', '<' ) ) {
			return $this->cart->total;
		}

		return $this->cart->get_total( null );
	}

}
