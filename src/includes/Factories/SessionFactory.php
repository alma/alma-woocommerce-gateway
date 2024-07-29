<?php
/**
 * SessionFactory.
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
 * Class SessionFactory.
 */
class SessionFactory {

	/**
	 * Get woocommerce session.
	 *
	 * @return \WC_Session|\WC_Session_Handler|null
	 */
	public function get_session() {
		return WC()->session;
	}
}
