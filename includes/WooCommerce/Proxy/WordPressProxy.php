<?php

namespace Alma\Gateway\WooCommerce\Proxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsProxy to manage WordPress/WooCommerce settings.
 */
class WordPressProxy {

	public static function admin_url( $path = '', $scheme = 'admin' ): ?string {
		return admin_url( $path, $scheme );
	}

	public static function is_admin(): bool {
		return is_admin();
	}
}
