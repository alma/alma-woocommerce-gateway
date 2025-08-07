<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Proxy;

use Alma\API\Domain\Entity\Order;
use Alma\API\Domain\OrderInterface;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\WooCommerce\Gateway\AbstractGateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
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

	public static function is_cart_product_or_checkout_page(): bool {
		return self::is_cart_page() || self::is_product_page() || self::is_checkout_page();
	}

	/**
	 * Get the cart total in cents.
	 *
	 * @return int
	 */
	public static function get_cart_total(): int {

		return DisplayHelper::price_to_cent( WC()->cart->get_total( null ) );
	}

	/**
	 * Get the order total in cents.
	 *
	 * @param string $order_id
	 *
	 * @return int
	 */
	public static function get_order_total( string $order_id ): int {
		return DisplayHelper::price_to_cent( wc_get_order( $order_id )->get_total() );
	}

	/**
	 * Get all Alma gateways.
	 *
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
	 * Redirects to the return URL after payment.
	 * This method is used to redirect the user to the return URL after a successful payment.
	 * It retrieves the return URL from the payment method and redirects the user to that URL.
	 * If the return URL is not set, it falls back to the cart URL.
	 *
	 * @param OrderInterface $order The order object containing the payment method and return URL.
	 *
	 * @return void
	 */
	public static function redirect_after_payment( OrderInterface $order ) {

		// Get the return url from the payment method
		$payment_method = $order->get_wc_order()->get_payment_method();
		$url            = WC()->payment_gateways()->payment_gateways()[ $payment_method ]->get_return_url( $order->get_wc_order() );
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

	public static function is_product_page(): bool {
		return is_product();
	}

	/**
	 * Get the current product price in cents.
	 *
	 * @return int
	 */
	public static function get_current_product(): int {
		if ( ! self::is_product_page() ) {
			return 0;
		}

		return get_the_ID();
	}

	/**
	 * Get the cart items.
	 *
	 * @return array
	 */
	public static function get_cart_items_categories(): array {

		$category_ids = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];

			if ( ! $product || ! $product->get_id() ) {
				continue;
			}

			$terms = get_the_terms( $product->get_id(), 'product_cat' );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$category_ids = array_merge( $category_ids, wp_list_pluck( $terms, 'term_id' ) );
			}
		}

		return array_values( array_unique( $category_ids ) );
	}

	/**
	 * Check if we are on the gateway settings page.
	 * It's an AJAX request to the REST API, so we check if the 'rest_route' key exists in the $_GET array
	 *
	 * @return bool True if we are on the gateway settings page, false otherwise.
	 */
	public static function is_gateway_settings_page(): bool {
		almalog( var_export( $_GET, true ) );
		if ( array_key_exists( 'rest_route', $_GET ) && stripos( $_GET['rest_route'], '/wc-admin' ) !== false ) {
			return true;
		}

		return false;
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
