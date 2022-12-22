<?php
/**
 * Alma_Api_Full_Refund.
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
 * Alma_Api_Full_Refund.
 */
class Alma_Api_Full_Refund extends \Exception {


	/**
	 * Constructor.
	 *
	 * @param string $transaction_id The transaction id.
	 * @param string $merchant_reference The merchant reference.
	 */
	public function __construct( $transaction_id, $merchant_reference ) {
		$message = sprintf(
			'Error while full refund. Transaction id : %s, Merchant reference : %s',
			$transaction_id,
			$merchant_reference
		);

		parent::__construct( $message );
	}
}
