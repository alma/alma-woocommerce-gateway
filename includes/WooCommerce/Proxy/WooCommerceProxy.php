<?php

namespace Alma\Gateway\WooCommerce\Proxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsProxy to manage WordPress/WooCommerce settings.
 */
class WooCommerceProxy extends WordPressProxy {

	public static function get_version() {
		return WC()->version;
	}

	/**
	 * Returns true if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_loaded() {
		return (bool) did_action( 'woocommerce_loaded' );
	}
}
