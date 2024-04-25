<?php
/**
 * SessionHelper.
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
 * Class SessionHelper.
 */
class SessionHelper {

	/**
	 * Get woocommerce session.
	 *
	 * @codeCoverageIgnore
	 * @return \WC_Session|\WC_Session_Handler|null
	 */
	public function get_session() {
		return WC()->session;
	}
}
