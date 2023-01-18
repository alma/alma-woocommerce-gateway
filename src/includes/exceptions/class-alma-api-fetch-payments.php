<?php
/**
 * Alma_Api_Fetch_Payments.
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
 * Alma_Api_Fetch_Payments.
 */
class Alma_Api_Fetch_Payments extends \Exception {


	/**
	 * Constructor.
	 *
	 * @param string $payment_id The payment id.
	 */
	public function __construct( $payment_id ) {
		$message = sprintf(
			'Error while fetching payment. Payment id : "%s"',
			$payment_id
		);

		parent::__construct( $message );
	}
}
