<?php
/**
 * VersionFactory.
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
 * Class VersionFactory.
 */
class VersionFactory {
	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		return WC()->version;
	}
}
