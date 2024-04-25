<?php
/**
 * VersionHelper.
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
 * Class VersionHelper.
 */
class VersionHelper {
	/**
	 * Get version.
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function get_version() {
		return WC()->version;
	}
}
