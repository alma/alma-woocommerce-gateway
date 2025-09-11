<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Helper\ContextHelperInterface;

class ContextHelper implements ContextHelperInterface {

	/**
	 * Get an admin url with the given path and scheme.
	 *
	 * @param string $path The path to append to the admin URL.
	 * @param string $scheme The scheme to use for the URL, default is 'admin'.
	 *
	 * @return string|null
	 */
	public static function adminUrl( string $path = '', string $scheme = 'admin' ): ?string {
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
	 * Check if we are on the gateway settings page.
	 * - It's an AJAX request to the REST API, so we check if the 'rest_route' key exists in the $_GET array
	 * - It's a regular page load, so we check if the 'page' key exists in the $_GET array
	 *
	 * @return bool True if we are on the gateway settings page, false otherwise.
	 */
	public function isGatewaySettingsPage(): bool {
		// AJAX request
		if ( array_key_exists( 'rest_route', $_GET ) && stripos( $_GET['rest_route'], '/wc-admin' ) !== false ) {
			return true;
		}
		// Regular page load
		if ( array_key_exists( 'page', $_GET ) && stripos( $_GET['page'], 'wc-settings' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we are on the cart page.
	 * @return bool
	 */
	public function isCartPage(): bool {
		return is_cart();
	}

	/**
	 * Check if we are on the checkout page.
	 * @return bool
	 */
	public function isCheckoutPage(): bool {
		return is_checkout();
	}

	/**
	 * Check if we are on the product page.
	 * @return bool
	 */
	public function isProductPage(): bool {
		return is_product();
	}

	/**
	 * Check if we are on the cart, product or checkout page.
	 * @return bool
	 */
	public function isCartProductOrCheckoutPage(): bool {
		return $this->isCartPage() || $this->isProductPage() || $this->isCheckoutPage();
	}

	/**
	 * Defines if the current request is an admin request.
	 * is_admin is not accurate for REST API requests.
	 * So we look for the 'rest_route' parameter in the $_GET superglobal to determine if it's an admin REST API request.
	 *
	 * @return bool True if the current request is an admin request, false otherwise.
	 * @phpcs We don't need to check nonce here. We only check the url, and we don't use parameters.
	 */
	public function isAdmin(): bool {
		// phpcs:ignore
		if ( array_key_exists( 'rest_route', $_GET ) && stripos( $_GET['rest_route'], '/wc-admin' ) !== false ) {
			return true;
		} else {
			return is_admin();
		}
	}

	/**
	 * Get the current language.
	 *
	 * @return string The current locale, default is 'fr'.
	 */
	public function getLanguage(): string {
		return substr( $this->getLocale() ?? 'fr_FR', 0, 2 );
	}

	/**
	 * Get the current locale.
	 *
	 * @return string The current locale, default is 'fr_FR'.
	 */
	public function getLocale(): string {
		return get_locale() ?? 'fr_FR';
	}

	/**
	 * Returns the current WooCommerce version.
	 *
	 * @return string
	 */
	public function getCmsVersion(): string {
		return WC()->version;
	}

	/**
	 * Returns true if WooCommerce is active.
	 *
	 * @return bool
	 */
	public function isCmsLoaded(): bool {
		return (bool) did_action( 'woocommerce_loaded' );
	}

	/**
	 * Get webhook url
	 *
	 * @param string $webhook Webhook.
	 *
	 * @return string
	 */
	public function getWebhookUrl( string $webhook ): string {
		return wc()->api_request_url( $webhook );
	}

	/**
	 * Get the current product price in cents.
	 *
	 * @return int
	 */
	public function getCurrentProduct(): int {
		if ( ! $this->isProductPage() ) {
			return 0;
		}

		return get_the_ID();
	}

}
