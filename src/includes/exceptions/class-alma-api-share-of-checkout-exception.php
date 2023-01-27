<?php
/**
 * Alma_Api_Share_Of_Checkout_Exception.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Api_Share_Of_Checkout_Exception.
 */
class Alma_Api_Share_Of_Checkout_Exception extends Alma_Exception {


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
