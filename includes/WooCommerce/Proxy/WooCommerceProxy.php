<?php

namespace Alma\Gateway\WooCommerce\Proxy;

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
		return is_checkout() || is_cart();
	}

	/**
	 * Get the cart total in cents.
	 *
	 * @return int
	 */
	public static function get_cart_total(): int {

		return 100 * WC()->cart->get_total( null );
	}

	public static function setEligibilities( $eligibility_data ): void {
		$available_gateways = WC()->payment_gateways()->payment_gateways();

		// Pour chaque gateway, on vérifie si elle est éligible
	}
}
