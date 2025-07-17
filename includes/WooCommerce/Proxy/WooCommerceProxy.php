<?php

namespace Alma\Gateway\WooCommerce\Proxy;

use Alma\Gateway\WooCommerce\Exception\CoreException;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;
use WC_Order;

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

		return self::price_to_cent( WC()->cart->get_total( null ) );
	}

	/**
	 * Get the order total in cents.
	 *
	 * @param string $order_id
	 *
	 * @return int
	 */
	public static function get_order_total( string $order_id ): int {
		return self::price_to_cent( wc_get_order( $order_id )->get_total() );
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
	 * Get webhook url
	 *
	 * @param string $webhook Webhook.
	 *
	 * @return string
	 */
	public static function get_webhook_url( string $webhook ): string {
		return wc()->api_request_url( $webhook );
	}

	public static function empty_cart() {
		wc()->cart->empty_cart();
	}

	/**
	 * Get an order by ID and key.
	 * @throws CoreException
	 */
	public static function get_order( $order_id, $order_key = null, $payment_id = null ) {
		$order = wc_get_order( $order_id );

		if ( ! $order && $order_key ) {
			// We have an invalid $order_id, probably because invoice_prefix has changed.
			$order_id = wc_get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );
		}

		if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			throw new CoreException( $order_id, $order_key, $payment_id );
		}

		return $order;
	}

	/**
	 * Convert a price in euros to cents.
	 *
	 * @param int $price The price in euros.
	 *
	 * @return int The price in cents.
	 */
	public static function price_to_cent( int $price ): int {
		return $price * 100;
	}

	/**
	 * Convert a price in cents to euros.
	 *
	 * @param int $price The price in cents.
	 *
	 * @return float|int The price in euros.
	 */
	public static function price_to_euro( int $price ) {
		return $price / 100;
	}

	/**
	 * Redirects to the return URL after payment.
	 * This method is used to redirect the user to the return URL after a successful payment.
	 * It retrieves the return URL from the payment method and redirects the user to that URL.
	 * If the return URL is not set, it falls back to the cart URL.
	 *
	 * @param WC_Order $wc_order The WooCommerce order object.
	 *
	 * @return void
	 */
	public static function redirect_after_payment( WC_Order $wc_order ) {

		// Get the return url from the payment method
		$payment_method = $wc_order->get_payment_method();
		$url            = WC()->payment_gateways()->payment_gateways()[ $payment_method ]->get_return_url( $wc_order );
		// If the return url is not set, fallback to the cart url
		if ( ! $url ) {
			$url = wc_get_cart_url();
		}
		wp_safe_redirect( $url );
		exit;
	}

	public static function redirect_to_cart( $message = null ) {
		if ( $message ) {
			self::notify_error( $message );
		}
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	/**
	 * Get something in WC session
	 *
	 * @param string $key
	 * @param        $default_session
	 *
	 * @return array|string|null
	 */
	public function get_session( string $key, $default_session = null ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return null;
		}

		return WC()->session->get( $key, $default_session );
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
