<?php
/**
 * Alma_Build_Order.
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
 * Alma_Build_Order.
 */
class Alma_Build_Order extends \Exception {


	/**
	 * Constructor.
	 *
	 * @param string $order_id The order id.
	 * @param string $order_key The order key.
	 * @param string $payment_id The payment id.
	 */
	public function __construct( $order_id, $order_key, $payment_id ) {
		$message = sprintf(
			'Error building order associated to payment. Order id "%s", Order Key "%s", Payment Id "%s"',
			$order_id,
			$order_key,
			$payment_id
		);

		parent::__construct( $message );
	}
}
