<?php
/**
 * BuildOrderException.
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
 * BuildOrderException.
 */
class BuildOrderException extends AlmaException {


	/**
	 * Constructor.
	 *
	 * @param string $order_id The order id.
	 * @param string $order_key The order key.
	 * @param string $payment_id The payment id.
	 */
	public function __construct( $order_id, $order_key, $payment_id = null ) {
		if ( ! empty( $payment_id ) ) {
			$message = $this->get_message_with_id_payment( $order_id, $order_key, $payment_id );
		} else {
			$message = $this->get_message_without_id_payment( $order_id, $order_key );
		}

		parent::__construct( $message );
	}

	/**
	 * Message with payment id.
	 *
	 * @param string $order_id The order id.
	 * @param string $order_key The order key.
	 * @param string $payment_id The payment id.
	 *
	 * @return string
	 */
	public function get_message_with_id_payment( $order_id, $order_key, $payment_id ) {
		return sprintf(
			'Error building order associated to payment. Order id "%s", Order Key "%s", Payment Id "%s"',
			$order_id,
			$order_key,
			$payment_id
		);
	}

	/**
	 * Message without payment id.
	 *
	 * @param string $order_id The order id.
	 * @param string $order_key The order key.
	 *
	 * @return string
	 */
	public function get_message_without_id_payment( $order_id, $order_key ) {
		return sprintf(
			'No order found. Order id "%s", Order Key "%s"',
			$order_id,
			$order_key
		);
	}
}
