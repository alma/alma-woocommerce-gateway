<?php
/**
 * ApiFetchPaymentsException.
 *
 * @since 4.0.0
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
 * ApiFetchPaymentsException.
 */
class ApiFetchPaymentsException extends AlmaException {


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
