<?php

namespace Alma\Gateway\WooCommerce\Proxy;

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
}
