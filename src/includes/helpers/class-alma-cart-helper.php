<?php
/**
 * Alma_Cart_Helper.
 *
 * @since 4.?
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Alma_Logger;

/**
 * Class Alma_Cart_Helper.
 */
class Alma_Cart_Helper {

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
		if ( ! wc()->cart ) {
			return 0;
		}

		if ( version_compare( WC()->version, '3.2.0', '<' ) ) {
			return wc()->cart->total;
		}

		$total = wc()->cart->get_total( null );

		if (
			0 === $total
			&& ! empty( WC()->session->get( 'cart_totals', null )['total'] )
		) {
			$total = WC()->session->get( 'cart_totals', null )['total'];
		}

		return $total;
	}
}
