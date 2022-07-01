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
	 * @param WC_Order_Refund $refund A Refund object.
	 * @return int
	 */
	public function get_amount_to_refund( $refund ) {
		return alma_wc_price_to_cents( floatval( $refund->get_amount() ) );
	}

	/**
	 * Gets the amount to refund for display on the page.
	 *
	 * @param WC_Order_Refund $refund A Refund object.
	 * @return string
	 */
	public function get_amount_to_refund_for_display( $refund ) {
		return $refund->get_amount() . ' ' . $refund->get_currency();
	}

	/**
	 * Gets the comment of a refund (which is optional).
	 *
	 * @param WC_Order_Refund $refund A Refund object.
	 * @return string
	 */
	public function get_refund_comment( $refund ) {
		return $refund->get_reason();
	}

	/**
	 * Adds an order note, and a back-office notice.
	 *
	 * @param WC_Order $order An order.
	 * @param string   $notice_type Notice type.
	 * @param string   $message Message to display.
	 * @return void
	 */
	public function add_order_note( $order, $notice_type, $message ) {

		$order->add_order_note( $message );

		$refund_notices   = get_post_meta( $order->get_id(), 'alma_refund_notices', false );
		$refund_notices[] = array(
			'notice_type' => $notice_type,
			'message'     => $message,
		);
		update_post_meta( $order->get_id(), 'alma_refund_notices', $refund_notices );
	}

	/**
	 * Callback function for the event "order status changed".
	 *
	 * @param integer $order_id Order id.
	 * @param string  $previous_status Order status before it changes.
	 * @param string  $next_status Order status affected to the order.
	 * @return void
	 */
	public function woocommerce_order_status_changed( $order_id, $previous_status, $next_status ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$order = wc_get_order( $order_id );
		if (
			'refunded' === $next_status &&
			true === $this->is_order_valid_for_full_refund_with_alma( $order )
		) {
			$this->make_full_refund( $order );
		}
	}

	/**
	 * Make full refund.
	 *
	 * @param WC_Order $order An order.
	 * @param integer  $refund_id Refund id.
	 * @return void
	 */
	public function make_full_refund( $order, $refund_id = 0 ) {

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			$this->add_order_note( $order, 'error', __( 'Alma API client init error.', 'alma-gateway-for-woocommerce' ) );
			return;
		}

		$merchant_reference = $order->get_order_number();
		// This text is not translated, as it is just made to be sent to Alma API.
		$comment = 'Refund made by order status changed to "refunded".';
		if ( $refund_id ) {
			$refund  = new WC_Order_Refund( $refund_id );
			$comment = $this->get_refund_comment( $refund );
		}
		try {
			$alma->payments->fullRefund( $order->get_transaction_id(), $merchant_reference, Alma_WC_Refund::PREFIX_REFUND_COMMENT . $comment );

			/* translators: %s is a username. */
			$order_note = sprintf( __( 'Order fully refunded by %s.', 'alma-gateway-for-woocommerce' ), wp_get_current_user()->display_name );
			$this->add_order_note( $order, 'success', $order_note );
		} catch ( RequestError $e ) {
			/* translators: %s is an error message. */
			$error_message = sprintf( __( 'Alma full refund error : %s.', 'alma-gateway-for-woocommerce' ), alma_wc_get_request_error_message( $e ) );
			$this->add_order_note( $order, 'error', $error_message );
			$this->logger->error( $error_message );
		}
	}

	/**
	 * Tells if the order is valid for a partial refund.
	 *
	 * @param WC_Order        $order An order.
	 * @param WC_Order_Refund $refund A Refund object.
	 * @return bool
	 */
	public function is_order_valid_for_partial_refund_with_alma( $order, $refund ) {
		$is_valid = true;

		if ( ! $this->is_alma_payment_method_for_order( $order ) ) {
			$is_valid = false;
		}

		if ( ! $this->has_order_a_transaction_id( $order ) ) {
			$is_valid = false;
		}

		$amount_to_refund = $this->get_amount_to_refund( $refund );
		if ( 0 === $amount_to_refund ) {
			$this->add_order_note( $order, 'error', __( 'Amount canno\'t be equal to 0 to refund with Alma.', 'alma-gateway-for-woocommerce' ) );
			$is_valid = false;
		}

		return $is_valid;
	}

	/**
	 * Tells if the order is valid for a full refund.
	 *
	 * @param WC_Order $order An order.
	 * @return bool
	 */
	public function is_order_valid_for_full_refund_with_alma( $order ) {
		$is_valid = true;

		if ( ! $this->is_alma_payment_method_for_order( $order ) ) {
			$is_valid = false;
		}

		if ( ! $this->has_order_a_transaction_id( $order ) ) {
			$is_valid = false;
		}

		return $is_valid;
	}

	/**
	 * Has this order been paid via Alma payment method ?.
	 *
	 * @param WC_Order $order An order.
	 * @return bool
	 */
	private function is_alma_payment_method_for_order( $order ) {
		if ( substr( $order->get_payment_method(), 0, 4 ) !== 'alma' ) {
			return false;
		}
		return true;
	}

	/**
	 * Has this order a transaction id ?.
	 *
	 * @param WC_Order $order An order.
	 * @return bool
	 */
	private function has_order_a_transaction_id( $order ) {
		if ( ! $order->get_transaction_id() ) {
			/* translators: %s is an order number. */
			$error_message = sprintf( __( 'Error while getting transaction_id on trigger_payment for order_id : %s.', 'alma-gateway-for-woocommerce' ), $order->get_id() );
			$this->add_order_note( $order, 'error', $error_message );
			$this->logger->error( $error_message );
			return false;
		}
		return true;
	}
}



