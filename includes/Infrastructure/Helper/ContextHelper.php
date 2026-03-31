<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\CustomerAdapter;
use Alma\Plugin\Infrastructure\Adapter\CartAdapterInterface;
use Alma\Plugin\Infrastructure\Adapter\CustomerAdapterInterface;
use Alma\Plugin\Infrastructure\Helper\ContextHelperInterface;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

class ContextHelper implements ContextHelperInterface {

	/**
	 * Get an admin url with the given path and scheme.
	 *
	 * @param string $path The path to append to the admin URL.
	 * @param string $scheme The scheme to use for the URL, default is 'admin'.
	 *
	 * @return string|null
	 */
	public static function getAdminUrl( string $path = '', string $scheme = 'admin' ): ?string {
		return admin_url( $path, $scheme );
	}

	/**
	 * Get the URL of an attachment.
	 *
	 * @param int $attachment_id The ID of the attachment.
	 *
	 * @return false|string
	 */
	public static function getAttachmentUrl( int $attachment_id = 0 ) {
		return wp_get_attachment_url( $attachment_id ) ? wp_get_attachment_url( $attachment_id ) : '';
	}

	/**
	 * Defines if the current request is an admin request.
	 * is_admin is not accurate for REST API requests.
	 * So we look for the 'rest_route' parameter in the $_GET superglobal to determine if it's an admin REST API request.
	 * For pretty permalinks, we check $_SERVER['REQUEST_URI'] which is always available from the start of the request.
	 * @return bool True if the current request is an admin request, false otherwise.
	 * @phpcs We don't need to check nonce here. We only check the url, and we don't use parameters.
	 *
	 */
	public static function isAdmin(): bool {
		// Classic admin pages (most common case).
		if ( is_admin() ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We only inspect the URL, no data is used.
		if ( array_key_exists( 'rest_route', $_GET ) && stripos( $_GET['rest_route'], '/wc-admin' ) !== false ) {
			return true;
		}

		// REST API with pretty permalinks (e.g. /wp-json/wc-admin/...).
		// $_SERVER['REQUEST_URI'] is always available from the very start of the request,
		// unlike $GLOBALS['wp']->query_vars or REST_REQUEST which depend on parse_request timing.
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && stripos( $_SERVER['REQUEST_URI'], '/wc-admin' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we are on the cart page (with or without blocks).
	 * @return bool
	 */
	public static function isCartPage(): bool {
		if ( ! did_action( 'template_redirect' ) ) {
			_doing_it_wrong( 'ContextHelper::isCartPage', 'We don\'t know yet the typ of page we are on.', '*' );
		}

		// Always use the standard is_cart() function which works for both blocks and shortcode
		return function_exists( 'is_cart' ) && is_cart();
	}

	/**
	 * Check if we are on the checkout page (with or without blocks).
	 * @return bool
	 */
	public static function isCheckoutPage(): bool {
		if ( ! did_action( 'template_redirect' ) ) {
			_doing_it_wrong( 'ContextHelper::isCheckoutPage', 'We don\'t know yet the typ of page we are on.', '*' );
		}

		// Always use the standard is_checkout() function which works for both blocks and shortcode
		return function_exists( 'is_checkout' ) && is_checkout();
	}

	/**
	 * Check if we are on the product page.
	 * @return bool
	 */
	public static function isProductPage(): bool {
		if ( ! did_action( 'template_redirect' ) ) {
			_doing_it_wrong( 'ContextHelper::isProductPage', 'We don\'t know yet the typ of page we are on.', '*' );
		}

		return is_product();
	}

	/**
	 * Check if we are on the cart, product or checkout page.
	 * @return bool
	 */
	public static function isShop(): bool {
		if ( ! did_action( 'template_redirect' ) ) {
			_doing_it_wrong( 'ContextHelper::isShop', 'We don\'t know yet the typ of page we are on.', '*' );
		}

		return self::isCartPage() || self::isProductPage() || self::isCheckoutPage();
	}

	/**
	 * Get the current language.
	 *
	 * @return string The current locale, default is 'fr'.
	 */
	public static function getLanguage(): string {
		return substr( self::getLocale() ?? 'fr_FR', 0, 2 );
	}

	/**
	 * Get the current locale.
	 *
	 * @return string The current locale, default is 'fr_FR'.
	 */
	public static function getLocale(): string {
		return get_locale() ?? 'fr_FR';
	}

	/**
	 * Get webhook url
	 *
	 * @param string $webhook Webhook.
	 *
	 * @return string
	 */
	public static function getWebhookUrl( string $webhook ): string {
		return wc()->api_request_url( $webhook );
	}

	/**
	 * Returns the current WordPress version.
	 *
	 * @return string
	 */
	public static function getPlatformVersion(): string {
		global $wp_version;

		return $wp_version;
	}

	/**
	 * Returns the current WooCommerce version.
	 *
	 * @return string
	 */
	public static function getCmsVersion(): string {
		return WC()->version;
	}

	/**
	 * Returns true if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function isCmsLoaded(): bool {
		return (bool) did_action( 'woocommerce_loaded' );
	}

	/**
	 * Get the current product price in cents.
	 *
	 * @return int
	 */
	public static function getCurrentProductId(): int {
		if ( ! self::isProductPage() ) {
			return 0;
		}

		return get_the_ID();
	}

	/**
	 * Get the current user ID.
	 *
	 * @return int
	 */
	public static function getCurrentUserId(): int {
		return get_current_user_id();
	}

	/**
	 * Check if we are on the gateway settings page.
	 * - It's an AJAX request to the REST API, so we check if the 'rest_route' key exists in the $_GET array
	 * - It's a regular page load, so we check if the 'page' key exists in the $_GET array
	 *
	 * @param bool $isAlmaGatewaySettingPage Whether to check specifically for Alma gateway settings page.
	 *
	 * @return bool True if we are on the gateway settings page, false otherwise.
	 */
	public static function isGatewaySettingsPage( bool $isAlmaGatewaySettingPage = false ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We only inspect the URL, no data is used.
		if ( isset( $_GET['rest_route'] ) && stripos( $_GET['rest_route'], '/wc-admin' ) !== false ) {
			return true;
		}

		// REST API request with pretty permalinks (e.g. /wp-json/wc-admin/settings/payments/...).
		// We don't gate on wp_is_serving_rest_request() because it depends on REST_REQUEST
		// which may not be defined yet when this function is called early.
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && stripos( $_SERVER['REQUEST_URI'], '/wc-admin/' ) !== false ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We only inspect the URL, no data is used.
		if ( ! isset( $_GET['page'] ) || stripos( $_GET['page'], 'wc-settings' ) === false ) {
			return false;
		}

		// Check specifically for Alma gateway settings page
		if ( $isAlmaGatewaySettingPage ) {
			return isset( $_GET['section'] ) && stripos( $_GET['section'], 'alma_config_gateway' ) !== false;
		}

		return true;
	}

	/**
	 * Get the current Cart instance.
	 *
	 * @return CartAdapterInterface
	 */
	public static function getCart(): ?CartAdapterInterface {
		return new CartAdapter( WC()->cart );
	}

	/**
	 * Get the current Customer instance.
	 *
	 * @return CustomerAdapterInterface
	 */
	public static function getCustomer(): ?CustomerAdapterInterface {
		return new CustomerAdapter( WC()->customer );
	}

	/**
	 * Check if the current request is an AJAX request.
	 * Multiple types of AJAX requests are possible in WordPress/WooCommerce so we use many methods to detect them.
	 *
	 * @return bool True if the current request is an AJAX request, false otherwise.
	 */
	public static function isAjax(): bool {

		$ajax = false;

		// AJAX Call
		if ( wp_doing_ajax() ) {
			$ajax = true;
		}

		// REST API Call (after parse_request)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$ajax = true;
		}

		// REST API Call (after parse_request)
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$uri = $_SERVER['REQUEST_URI'];

			// Store API call
			if ( strpos( $uri, '/wc/store/' ) !== false ) {
				$ajax = true;
			}

			// general REST API call
			if ( wp_is_serving_rest_request() ) {
				// rest_route=/wc/store/v1/checkout&_locale=site
				$ajax = true;
			}
		}

		return $ajax;
	}

	/**
	 * Check if the checkout page is using blocks.
	 *
	 * @return bool True if the checkout page is using blocks, false otherwise.
	 */
	public static function isCheckoutPageUseBlocks(): bool {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) &&
		     method_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default' ) ) {

			return CartCheckoutUtils::is_checkout_block_default();
		}

		// Fallback: check if the checkout page has the checkout block
		$checkout_page_id = wc_get_page_id( 'checkout' );
		if ( $checkout_page_id ) {
			return has_block( 'woocommerce/checkout', $checkout_page_id );
		}

		return false;
	}

	/**
	 * Check if the cart page is using blocks.
	 *
	 * @return bool True if the cart page is using blocks, false otherwise.
	 */
	public static function isCartPageUseBlocks(): bool {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) &&
		     method_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_cart_block_default' ) ) {
			return CartCheckoutUtils::is_cart_block_default();
		}

		// Fallback: check if the cart page has the cart block
		$cart_page_id = wc_get_page_id( 'cart' );
		if ( $cart_page_id ) {
			return has_block( 'woocommerce/cart', $cart_page_id );
		}

		return false;
	}
}
