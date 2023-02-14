<?php
/**
 * Alma_Amount_Mismatch_Exception.
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
 * Alma_Amount_Mismatch_Exception.
 */
class Alma_Amount_Mismatch_Exception extends Alma_Exception {


	/**
	 * Constructor.
	 *
	 * @param string $payment_id The payment id.
	 * @param string $order_id The order id.
	 * @param string $order_total The order total.
	 * @param string $purchase_amount The purchase amount.
	 */
	public function __construct( $payment_id, $order_id, $order_total, $purchase_amount ) {
		$message = sprintf(
			'FlagAsPotentialFraud request error (total amount issue), Payment Id "%s". Order "%s" total "(%s)" does not match purchase amount of  "(%s)"',
			$payment_id,
			$order_id,
			$order_total,
			$purchase_amount
		);

		parent::__construct( $message );
	}
}
