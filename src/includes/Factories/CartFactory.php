<?php
/**
 * CartFactory.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Factories
 * @namespace Alma\Woocommerce\Factories
 */

namespace Alma\Woocommerce\Factories;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CartFactory.
 */
class CartFactory {

	/**
	 * Get Wc cart
	 *
	 * @return \WC_Cart|null
	 */
	public function get_cart() {
		return wc()->cart;
	}
}
