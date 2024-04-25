<?php
/**
 * CurrencyHelper.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CurrencyHelper.
 */
class CurrencyHelper {
	/**
	 * Get currency.
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function get_currency() {
		return get_woocommerce_currency();
	}
}
