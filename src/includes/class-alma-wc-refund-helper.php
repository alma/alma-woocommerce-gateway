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
	const PREFIX_REFUND_COMMENT     = 'Refund made via WooCommerce back-office - ';
	const FLAG_ORDER_FULLY_REFUNDED = 'alma_order_fully_refunded';
	const NOTICE_TYPE_ERROR         = 'error';
	const NOTICE_TYPE_SUCCESS       = 'success';
	const REFUND_NOTICE_META_KEY    = 'alma_refund_notices';

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Order messages
	 *
	 * @var array
	 */
	private $messages = array();

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
	public function get_refund_amount( $refund ) {
		return alma_wc_price_to_cents( floatval( $refund->get_amount() ) );
	}

	/**
	 * Gets the amount to refund for display on the page.
	 *
	 * @param WC_Order_Refund $refund A Refund object.
	 * @return string
	 */
	public function get_display_refund_amount( $refund ) {
		return $refund->get_amount() . ' ' . $refund->get_currency();
	}

	/**
	 * Adds a refund order note, and a back-office notice.
	 *
	 * @param WC_Order $order An order.
	 * @param string   $notice_type Notice type.
	 * @param string   $message Message to display.
	 *
	 * @return void
	 * @see add_notice()
	 */
	private function add_order_note( $order, $notice_type, $message ) {

		if ( in_array( $message, $this->messages ) ) {
			return;
		}
		$this->messages[] = $message;

		$order->add_order_note( $message );

		$this->add_notice( $order, $notice_type, $message );
	}

	/**
	 * Make full refund.
	 *
	 * @param WC_Order $order An order.
	 * @param integer  $refund_id Refund id.
	 * @return void
	 */
	public function make_full_refund( $order, $refund_id = 0 ) {

		if ( '1' === get_post_meta( $order->get_id(), self::FLAG_ORDER_FULLY_REFUNDED, true ) ) {
			return;
		}

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			$this->add_error_note( $order, __( 'Alma API client init error.', 'alma-gateway-for-woocommerce' ) );
			return;
		}

		$merchant_reference = $order->get_order_number();
		// This text is not translated, as it is just made to be sent to Alma API.
		$comment = 'Refund made by order status changed to "refunded".';
		if ( $refund_id ) {
			$refund  = new WC_Order_Refund( $refund_id );
			$comment = $refund->get_reason();
		}
		try {
			$alma->payments->fullRefund( $order->get_transaction_id(), $merchant_reference, $this->format_refund_comment( $comment ) );
			update_post_meta( $order->get_id(), self::FLAG_ORDER_FULLY_REFUNDED, '1' );

			/* translators: %s is a username. */
			$order_note = sprintf( __( 'Order fully refunded by %s.', 'alma-gateway-for-woocommerce' ), wp_get_current_user()->display_name );
			$this->add_success_note( $order, $order_note );
		} catch ( RequestError $e ) {
			/* translators: %s is an error message. */
			$error_message = sprintf( __( 'Alma full refund error : %s.', 'alma-gateway-for-woocommerce' ), $e->getErrorMessage() );
			$this->add_error_note( $order, $error_message );
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
	public function is_partially_refundable( $order, $refund ) {
		$has_valid_amount = $this->get_refund_amount( $refund ) > 0;
		if ( ! $has_valid_amount ) {
			$this->add_error_note( $order, __( 'Amount cannot be equal to 0 to refund with Alma.', 'alma-gateway-for-woocommerce' ) );
		}

		return $has_valid_amount && $this->is_fully_refundable( $order );
	}

	/**
	 * Tells if the order is valid for a full refund with Alma.
	 *
	 * @param WC_Order $order An order.
	 * @return bool
	 */
	public function is_fully_refundable( $order ) {
		return $this->is_paid_with_alma( $order ) && $this->has_transaction_id( $order );
	}

	/**
	 * Has this order been paid via Alma payment method ?.
	 *
	 * @param WC_Order $order An order.
	 * @return bool
	 */
	public function is_paid_with_alma( $order ) {
		return in_array(
			$order->get_payment_method(),
			array(
				Alma_WC_Payment_Gateway::GATEWAY_ID,
				Alma_WC_Payment_Gateway::ALMA_GATEWAY_PAY_LATER,
				Alma_WC_Payment_Gateway::ALMA_GATEWAY_PAY_MORE_THAN_FOUR,
			),
			true
		);
	}

	/**
	 * Has this order a transaction id ?.
	 *
	 * @param WC_Order $order An order.
	 * @return bool
	 */
	private function has_transaction_id( $order ) {
		if ( ! $order->get_transaction_id() ) {
			/* translators: %s is an order number. */
			$error_message = sprintf( __( 'Error while getting alma transaction_id for order_id : %s.', 'alma-gateway-for-woocommerce' ), $order->get_id() );
			$this->add_error_note( $order, $error_message );
			$this->logger->error( $error_message );
			return false;
		}
		return true;
	}

	/**
	 * Formats a comment by adding a default refund sentence prefix.
	 *
	 * @param string $comment The comment to prefix to.
	 *
	 * @return string
	 */
	public function format_refund_comment( $comment ) {
		return self::PREFIX_REFUND_COMMENT . $comment;
	}

	/**
	 * Adds an alma_refund_notices in post_meta
	 *
	 * @param WC_Order $order The order where to add a note & notice.
	 * @param string   $notice_type Notice type.
	 * @param string   $message The message.
	 *
	 * @return void
	 */
	protected function add_notice( WC_Order $order, $notice_type, $message ) {
		$refund_notices   = get_post_meta( $order->get_id(), self::REFUND_NOTICE_META_KEY, false );
		$refund_notices[] = array(
			'notice_type' => $notice_type,
			'message'     => $message,
		);
		update_post_meta( $order->get_id(), self::REFUND_NOTICE_META_KEY, $refund_notices );
	}

	/**
	 * Adds a refund error note to an order + a notice
	 *
	 * @param WC_Order $order The order where to add a note & notice.
	 * @param string   $message The message.
	 *
	 * @see add_order_note()
	 * @return void
	 */
	public function add_error_note( WC_Order $order, $message ) {
		$this->add_order_note( $order, self::NOTICE_TYPE_ERROR, $message );
	}

	/**
	 * Adds a refund success note to an order + a notice
	 *
	 * @param WC_Order $order The order where to add a note & notice.
	 * @param string   $message The message.
	 *
	 * @see add_order_note()
	 * @return void
	 */
	public function add_success_note( WC_Order $order, $message ) {
		$this->add_order_note( $order, self::NOTICE_TYPE_SUCCESS, $message );
	}

	/**
	 * Print refund notices previously stored as HTML format
	 *
	 * @param WC_Order $order The order linked to the refund notices.
	 *
	 * @return void
	 */
	public function print_notices( WC_Order $order ) {
		$refund_notices = get_post_meta( $order->get_id(), self::REFUND_NOTICE_META_KEY, true );

		if ( ! is_array( $refund_notices ) ) {
			return;
		}

		foreach ( $refund_notices as $notice_infos ) {
			if ( ! is_array( $notice_infos ) || ! isset( $notice_infos['message'] ) ) {
				continue;
			}
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_html( $notice_infos['notice_type'] ),
				esc_html( $notice_infos['message'] )
			);
		}
	}

	/**
	 * Deletes refund notices.
	 *
	 * @param WC_Order $order The order linked to the refund notices.
	 *
	 * @return void
	 */
	public function delete_notices( WC_Order $order ) {
		delete_post_meta( $order->get_id(), self::REFUND_NOTICE_META_KEY );
	}
}
