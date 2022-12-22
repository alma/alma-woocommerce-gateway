<?php
/**
 * Alma_Api_Trigger_Payments.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/exceptions
 * @namespace Alma_WC\Exceptions
 */

namespace Alma_WC\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Api_Trigger_Payments.
 */
class Alma_Api_Trigger_Payments extends \Exception {


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
