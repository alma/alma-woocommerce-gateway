<?php
/**
 * Functions proxy.
 *
 * @package Alma\Woocommerce\WcProxy
 */

namespace Alma\Woocommerce\WcProxy;

/**
 * Functions proxy.
 */
class FunctionsProxy {


	public static function is_admin() {
		if ( array_key_exists( 'rest_route', $_GET ) && stripos( $_GET['rest_route'], '/wc-admin' ) !== false ) {
			return true;
		} else {
			return is_admin();
		}
	}

	/**
	 * Send HTTP response.
	 *
	 * @param array    $response Response data.
	 * @param int|null $status_code HTTP status code.
	 * @param int      $flags Response flags.
	 */
	public function send_http_response( $response, $status_code = null, $flags = 0 ) {
		wp_send_json( $response, $status_code, $flags );
	}

	/**
	 * Send HTTP error response.
	 *
	 * @param array    $response Response data.
	 * @param int|null $status_code HTTP status code.
	 * @param int      $flags Response flags.
	 */
	public function send_http_error_response( $response, $status_code = null, $flags = 0 ) {
		wp_send_json_error( $response, $status_code, $flags );
	}
}
