<?php
/**
 * Alma Alma refund helper
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Refund_Helper
 */
class Alma_WC_Refund_Helper {

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger = new Alma_WC_Logger();
	}

	/**
	 * Gets the amount to refund.
	 *
	 * @param integer $refund_id Refund id.
	 * @param bool    $display Tells if amount is supposed to be used for calculation or display.
	 * @return int|string
	 */
	public function get_amount_to_refund( $refund_id, $display = false ) {
		$refund           = new WC_Order_Refund( $refund_id );
		$amount_to_refund = alma_wc_price_to_cents( floatval( $refund->get_amount() ) );
		if ( true === $display ) {
			$amount_to_refund = $refund->get_amount() . ' ' . $refund->get_currency();
		}
		return $amount_to_refund;
	}

	/**
	 * Gets the comment of a refund (which is optional).
	 *
	 * @param integer $refund_id Refund id.
	 * @return string
	 */
	public function get_refund_comment( $refund_id ) {
		$refund = new WC_Order_Refund( $refund_id );
		return $refund->get_reason();
	}

	/**
	 * Gets the merchant reference.
	 *
	 * @param integer $order_id An order id.
	 * @return string
	 */
	public function get_merchant_reference( $order_id ) {
		try {
			$alma_model_order = new Alma_WC_Model_Order( $order_id );
		} catch ( Exception $e ) {
			$this->logger->error( 'Error getting payment info from order: ' . $e->getMessage() );
			return null;
		}
		return $alma_model_order->get_order_reference();
	}

	/**
	 * Adds an order note, and a back-office notice.
	 *
	 * @param integer $order_id Order id.
	 * @param string  $notice_type Notice type.
	 * @param string  $message Message to display.
	 * @return void
	 */
	public function add_order_note( $order_id, $notice_type, $message ) {

		$order = wc_get_order( $order_id );
		$order->add_order_note( $message );

		$refund_notices   = get_post_meta( $order_id, 'alma_refund_notices', false );
		$refund_notices[] = array(
			'notice_type' => $notice_type,
			'message'     => $message,
		);
		update_post_meta( $order_id, 'alma_refund_notices', $refund_notices );
	}

	/**
	 * Callback function for the event "order status changed".
	 *
	 * @param integer $order_id The order id.
	 * @param string  $previous_status Order status before it changes.
	 * @param string  $next_status Order status affected to the order.
	 * @return void
	 */
	public function woocommerce_order_status_changed( $order_id, $previous_status, $next_status ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$order = wc_get_order( $order_id );
		if ( 'alma' !== substr( $order->get_payment_method(), 0, 4 ) ) {
			return;
		}

		if (
			alma_wc_plugin()->settings->refund_automatically_on_order_status_change === 'yes' &&
			'refunded' === $next_status
		) {
			$alma = alma_wc_plugin()->get_alma_client();
			if ( ! $alma ) {
				$this->add_order_note( $order_id, 'error', __( 'API client init error.', 'alma-woocommerce-gateway' ) );
				return;
			}
			$merchant_reference = $this->get_merchant_reference( $order_id );
			if ( null === $merchant_reference ) {
				/* translators: %s is an order number. */
				$message = sprintf( __( 'Full refund error : merchant reference is missing for order number %s.', 'alma-woocommerce-gateway' ) );
				$this->add_order_note( $order_id, 'error', $message );
				$this->logger->error( $message );
				return;
			}
			$refund_comment = __( 'Fully refunded via WooCommerce back-office on order status changed.', 'alma-woocommerce-gateway' );
			try {
				$alma->payments->fullRefund( $order->get_transaction_id(), $merchant_reference, $refund_comment );
			} catch ( Exception $e ) {
				$message = 'Error fullRefund : ' . $e->getMessage();
				$this->add_order_note( $order_id, 'error', $message );
				$this->logger->error( $message );
			}
		}
	}

	/**
	 * Tells if the order is valid for a partial or full refund.
	 *
	 * @param $order_id
	 * @param $refund_id
	 * @return bool
	 */
	public function is_order_valid_for_refund( $order_id, $refund_id ) {

		$is_order_valid_for_refund = true;

		$order = wc_get_order( $order_id );
		if ( substr( $order->get_payment_method(), 0, 4 ) !== 'alma' ) {
			$is_order_valid_for_refund = false;
		}

		if ( ! $order->get_transaction_id() ) {
			/* translators: %s is an order number. */
			$message = sprintf( __( 'Error while getting transaction_id on trigger_payment for order_id : %s.', 'alma-woocommerce-gateway' ), $order_id );
			$this->add_order_note( $order_id, 'error', $message );
			$this->logger->error( $message );
			$is_order_valid_for_refund = false;
		}

		$amount_to_refund = $this->get_amount_to_refund( $refund_id );
		if ( 0 === $amount_to_refund ) {
			$this->add_order_note( $order_id, 'error', __( 'Amount canno\'t be equal to 0 to refund with Alma.', 'alma-woocommerce-gateway' ) );
			$is_order_valid_for_refund = false;
		}

		$merchant_reference = $this->get_merchant_reference( $order_id );
		if ( null === $merchant_reference ) {
			/* translators: %s is an order number. */
			$message = sprintf( __( 'Partial refund error : merchant reference is missing for order number : %s.', 'alma-woocommerce-gateway' ), $order_id );
			$this->add_order_note( $order_id, 'error', $message );
			$this->logger->error( $message );
			$is_order_valid_for_refund = false;
		}

		return $is_order_valid_for_refund;
	}
}



