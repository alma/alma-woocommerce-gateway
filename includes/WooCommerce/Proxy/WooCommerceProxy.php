<?php

/**
 * @see https://developer.wordpress.org/plugins/settings/custom-settings-page/
 */

namespace Alma\Gateway\WooCommerce\Proxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsProxy to manage WordPress/WooCommerce settings.
 */
class WooCommerceProxy {

	public static function get_version() {
		return WC()->version;
	}
}
