<?php
/**
 * ApiShareOfCheckoutException.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ApiShareOfCheckoutException.
 */
class ApiShareOfCheckoutException extends AlmaException {


	/**
	 * Contruct.
	 *
	 * @param array $data The payload.
	 */
	public function __construct( $data ) {
		$message = sprintf(
			'Error while sharing soc data. Data : "%s"',
			wp_json_encode( $data )
		);

		parent::__construct( $message );
	}
}
