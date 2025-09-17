<?php

namespace Alma\Gateway\Infrastructure\Helper;

class AjaxHelper {

	/**
	 * Send a JSON response indicating success with a 200 OK status code.
	 *
	 * @param bool|null $data
	 */
	public static function sendSuccessResponse( $data = null ): void {
		$response = array( 'success' => true );

		if ( isset( $data ) ) {
			$response['data'] = $data;
		}
		wp_send_json( $response, 200 );
	}

	/**
	 * Send a JSON response with a 400 Bad Request status code.
	 *
	 * @param string $message The error message to send.
	 */
	public static function sendBadRequestResponse( string $message ): void {
		wp_send_json( array( 'error' => $message ), 400 );
	}

	/**
	 * Send a JSON response with a 401 Unauthorized status code.
	 *
	 * @param string $message The error message to send.
	 */
	public static function sendUnauthorizedResponse( string $message ): void {
		wp_send_json( array( 'error' => $message ), 401 );
	}

	/**
	 * Send a JSON response with a 403 Forbidden status code.
	 *
	 * @param string $message The error message to send.
	 */
	public static function sendForbiddenResponse( string $message ): void {
		wp_send_json( array( 'error' => $message ), 403 );
	}
}