<?php
/**
 * ApiPartialRefundException.
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
 * ApiPartialRefundException.
 */
class ApiPartialRefundException extends AlmaException {


	/**
	 * Constructor.
	 *
	 * @param string $transaction_id The transaction id.
	 * @param string $merchant_reference The merchant reference.
	 * @param string $amount The amount to refund.
	 */
	public function __construct( $transaction_id, $merchant_reference, $amount ) {
		$message = sprintf(
			'Error while full refund. Transaction id : "%s", Merchant reference : "%s", Amount : "%s"',
			$transaction_id,
			$merchant_reference,
			$amount
		);

		parent::__construct( $message );
	}
}
