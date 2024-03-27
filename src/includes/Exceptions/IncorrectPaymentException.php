<?php
/**
 * IncorrectPaymentException.
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
 * IncorrectPaymentException.
 */
class IncorrectPaymentException extends AlmaException {


	/**
	 * Constructor.
	 *
	 * @param string $payment_id The payment id.
	 * @param string $order_id The order id.
	 * @param string $payment_state The payment state.
	 */
	public function __construct( $payment_id, $order_id, $payment_state ) {
		$message = sprintf(
			'State payment error/incorrect, Order id "%s", Payment Id "%s", Payment State "%s" ',
			$order_id,
			$payment_id,
			$payment_state
		);

		parent::__construct( $message );
	}
}
