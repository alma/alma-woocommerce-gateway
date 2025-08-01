<?php

namespace Alma\Gateway\WooCommerce\Proxy;

use Alma\Gateway\Business\Service\OptionsService;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Class SettingsProxy to manage WordPress/WooCommerce settings.
 */
class WordPressProxy {

	/**
	 * Get an admin url with the given path and scheme.
	 *
	 * @param string $path The path to append to the admin URL.
	 * @param string $scheme The scheme to use for the URL, default is 'admin'.
	 *
	 * @return string|null
	 */
	public static function admin_url( string $path = '', string $scheme = 'admin' ): ?string {
		return admin_url( $path, $scheme );
	}

	/**
	 * Defines if the current request is an admin request.
	 * is_admin is not accurate for REST API requests.
	 * So we look for the 'rest_route' parameter in the $_GET superglobal to determine if it's an admin REST API request.
	 *
	 * @return bool True if the current request is an admin request, false otherwise.
	 * @phpcs We don't need to check nonce here. We only check the url, and we don't use parameters.
	 */
	public static function is_admin(): bool {
		// phpcs:ignore
		if ( array_key_exists( 'rest_route', $_GET ) && stripos( $_GET['rest_route'], '/wc-admin' ) !== false ) {
			return true;
		} else {
			return is_admin();
		}
	}

	/**
	 * Get the URL of an attachment.
	 *
	 * @param int $attachment_id The ID of the attachment.
	 *
	 * @return false|string
	 */
	public static function get_attachment_url( int $attachment_id = 0 ) {
		return wp_get_attachment_url( $attachment_id ) ? wp_get_attachment_url( $attachment_id ) : '';
	}

	/**
	 * Set a nonce field for form submission.
	 *
	 * @param string $nonce The nonce field name.
	 * @param string $action The action name for the nonce.
	 *
	 * @return void
	 */
	public static function set_nonce( string $nonce, string $action ): void {
		wp_nonce_field( $action, $nonce );
	}

	/**
	 * Checks if the nonce is valid.
	 *
	 * @param string $nonce The nonce field name.
	 * @param string $action The action name.
	 *
	 * @return bool True if the nonce is valid, false otherwise.
	 */
	public static function check_nonce( string $nonce, string $action ): bool {
		if ( ! isset( $_POST[ $nonce ] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_POST[ $nonce ], $action ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return an Error
	 *
	 * @param string $code The error code.
	 * @param string $message The error message.
	 * @param mixed  $data Additional data to include in the error.
	 *
	 * @return WP_Error The WP_Error object with the given message.
	 */
	public static function error( string $code, string $message, $data = '' ): WP_Error {
		return new WP_Error( $code, $message, $data );
	}

	/**
	 * Notify the user with an error message.
	 *
	 * @param string $message The error message to display.
	 *
	 * @return void
	 */
	public static function notify_error( string $message ) {
		wc_add_notice( $message, 'error' );
	}

	/**
	 * Notify the user with a notice message.
	 *
	 * @param string $message The notice message to display.
	 *
	 * @return void
	 */
	public static function notify_notice( string $message ) {
		wc_add_notice( $message, 'notice' );
	}

	/**
	 * Notify the user with a success message.
	 *
	 * @param string $message The success message to display.
	 *
	 * @return void
	 */
	public static function notify_success( string $message ) {
		wc_add_notice( $message );
	}

	/**
	 * Get the current language.
	 *
	 * @return string The current locale, default is 'fr'.
	 */
	public static function get_language(): string {
		return substr( get_locale() ?? 'fr_FR', 0, 2 );
	}

	/**
	 * Get the product categories.
	 *
	 * @return array The product categories, as an associative array with term IDs as keys and names as values.
	 */
	public static function get_categories(): array {
		$product_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'orderby'    => 'name',
				'order'      => 'asc',
				'hide_empty' => false,
			)
		);

		return array_combine(
			array_column( $product_categories, 'term_id' ),
			array_column( $product_categories, 'name' )
		);
	}

	/**
	 * Set the key encryptor for API keys.
	 *
	 * @return void
	 */
	public static function set_key_encryptor() {
		add_filter(
			'pre_update_option_' . OptionsProxy::OPTIONS_KEY,
			array( OptionsService::class, 'encrypt_keys' )
		);
	}

	/**
	 * Get the current locale.
	 *
	 * @return string The current locale, default is 'fr_FR'.
	 */
	public function get_locale(): string {
		return get_locale() ?? 'fr_FR';
	}
}
