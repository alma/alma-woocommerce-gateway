<?php
/**
 * Alma_Inpage_Helper.
 *
 * @since 4.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}



/**
 * Class Alma_Inpage_Helper.
 */
class Alma_Inpage_Helper {

	public function inpage_payload_alma() {
		try {

			// $value = sanitize_text_field( $_POST['accept'] ); // phpcs:ignore WordPress.Security.NonceVerification

			wp_send_json_success();
		} catch ( \Exception $e ) {

			wp_send_json_error( 'error', 500 ); // @todo choose error code
		}
	}
}
