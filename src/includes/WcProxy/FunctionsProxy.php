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

}
