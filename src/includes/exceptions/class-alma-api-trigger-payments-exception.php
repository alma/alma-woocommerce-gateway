<?php
/**
 * Alma_Api_Trigger_Payments_Exception.
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
 * Alma_Api_Trigger_Payments_Exception.
 */
class Alma_Api_Trigger_Payments_Exception extends Alma_Exception {


	/**
	 * Constructor.
	 *
	 * @param string $transaction_id The transaction id.
	 */
	public function __construct( $transaction_id ) {
		$message = sprintf(
			'Error while triggering payment. Transaction id : "%s"',
			$transaction_id
		);

		parent::__construct( $message );
	}
}
