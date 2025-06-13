<?php

namespace Alma\Gateway\WooCommerce\Proxy;

use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;

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
	 * Get the order total in cents.
	 *
	 * @param $order_id
	 *
	 * @return int
	 */
	public static function get_order_total( $order_id ): int {
		return 100 * wc_get_order( $order_id )->get_total();
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

	/**
	 * Get something in WC session
	 *
	 * @param string $key
	 * @param        $default
	 *
	 * @return array|string|null
	 */
	public function get_session( string $key, $default = null ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return null;
		}

		return WC()->session->get( $key, $default );
	}

	/**
	 * Set something in WC session
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set_session( string $key, $value ) {
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( $key, $value );
		}
	}
}
