<?php
/**
 * Alma_Incorrect_Payment.
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
 * Alma_Incorrect_Payment.
 */
class Alma_Incorrect_Payment extends \Exception {


	/**
	 * Constructor.
	 *
	 * @param string $payment_id The payment id.
	 * @param string $order_id The order id.
	 * @param string $payment_state The payment state.
	 * @param string $first_instalment_state The first instalment.
	 */
	public function __construct( $payment_id, $order_id, $payment_state, $first_instalment_state ) {
		$message = sprintf(
			'State payment error/incorrect, Order id "%s", Payment Id "%s", Payment State "%s", First instalement state "%s" ',
			$order_id,
			$payment_id,
			$payment_state,
			$first_instalment_state
		);

		parent::__construct( $message );
	}
}
