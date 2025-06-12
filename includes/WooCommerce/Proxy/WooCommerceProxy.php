<?php

namespace Alma\Gateway\WooCommerce\Proxy;

use Alma\Gateway\WooCommerce\Model\AbstractGateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsProxy to manage WordPress/WooCommerce settings.
 */
class WooCommerceProxy extends WordPressProxy {

	public static function get_version(): string {
		return WC()->version;
	}

	/**
	 * Returns true if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_loaded(): bool {
		return (bool) did_action( 'woocommerce_loaded' );
	}

	public static function is_cart_page(): bool {
		return is_cart();
	}

	public static function is_checkout_page(): bool {
		return is_checkout();
	}

	public static function is_cart_or_checkout_page(): bool {
		return self::is_cart_page() || self::is_checkout_page();
	}

	/**
	 * Get the cart total in cents.
	 *
	 * @return int
	 */
	public static function get_cart_total(): int {

		return 100 * WC()->cart->get_total( null );
	}

	/**
	 * Get all Alma gateways.
	 * @return array
	 */
	public static function get_alma_gateways(): array {
		return array_filter(
			WC()->payment_gateways()->payment_gateways(),
			function ( $gateway ) {
				return $gateway instanceof AbstractGateway;
			}
		);
	}
}
