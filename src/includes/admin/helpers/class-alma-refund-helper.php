<?php
/**
 * Alma_Refund_Helper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/admin/helpers
 * @namespace Alma\Woocommerce\Admin\Helpers
 */

namespace Alma\Woocommerce\Admin\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

/**
 * Alma_Admin_Helper_Refund
 */
class Alma_Refund_Helper {


	/**
	 * Logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;

	/**
	 * Order messages.
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * The settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;

	/**
	 * Helper global.
	 *
	 * @var Alma_Tools_Helper
	 */
	protected $tool_helper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger        = new Alma_Logger();
		$this->alma_settings = new Alma_Settings();
		$this->tool_helper   = new Alma_Tools_Helper();
	}

	/**
	 * Gets the amount to refund for display on the page.
	 *
	 * @param \WC_Order_Refund $refund A Refund object.
	 *
	 * @return string The display.
	 */
	public function get_display_refund_amount( $refund ) {
		return $refund->get_amount() . ' ' . $refund->get_currency();
	}

	/**
	 * Make full refund.
	 *
	 * @param \WC_Order $order An order.
	 * @param integer   $refund_id Refund id.
	 *
	 * @return void
	 */
	public function make_full_refund( $order, $refund_id = 0 ) {

		if ( '1' === get_post_meta( $order->get_id(), Alma_Constants_Helper::FLAG_ORDER_FULLY_REFUNDED, true ) ) {
			return;
		}

		$merchant_reference = $order->get_order_number();

		// This text is not translated, as it is just made to be sent to Alma API.
		$comment = 'Refund made by order status changed to "refunded".';

		if ( $refund_id ) {
			$refund  = new \WC_Order_Refund( $refund_id );
			$comment = $refund->get_reason();
		}

		try {
			$this->alma_settings->full_refund( $order->get_transaction_id(), $merchant_reference, $this->format_refund_comment( $comment ) );

			update_post_meta( $order->get_id(), Alma_Constants_Helper::FLAG_ORDER_FULLY_REFUNDED, '1' );

			/* translators: %s is a username. */
			$order_note = sprintf( __( 'Order fully refunded by %s.', 'alma-gateway-for-woocommerce' ), wp_get_current_user()->display_name );
			$this->add_success_note( $order, $order_note );

		} catch ( \Exception $e ) {
			/* translators: %s is an error message. */
			$this->add_error_note( $order, sprintf( __( 'Alma full refund error : %s.', 'alma-gateway-for-woocommerce' ), $e->getMessage() ) );

			$this->logger->error(
				'Error on Alma full refund.',
				array(
					'Method'           => __METHOD__,
					'OrderId'          => $order->get_id(),
					'RefundId'         => $refund_id,
					'ExceptionMessage' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Formats a comment by adding a default refund sentence prefix.
	 *
	 * @param string $comment The comment to prefix to.
	 *
	 * @return string
	 */
	public function format_refund_comment( $comment ) {
		return Alma_Constants_Helper::PREFIX_REFUND_COMMENT . $comment;
	}

	/**
	 * Adds a refund success note to an order + a notice
	 *
	 * @param \WC_Order $order The order where to add a note & notice.
	 * @param string    $message The message.
	 *
	 * @return void
	 * @see add_order_note()
	 */
	public function add_success_note( \WC_Order $order, $message ) {
		$this->add_order_note( $order, Alma_Constants_Helper::SUCCESS, $message );
	}

	/**
	 * Adds a refund order note, and a back-office notice.
	 *
	 * @param \WC_Order $order An order.
	 * @param string    $notice_type Notice type.
	 * @param string    $message Message to display.
	 *
	 * @return void
	 * @see add_notice()
	 */
	protected function add_order_note( $order, $notice_type, $message ) {

		if ( in_array( $message, $this->messages, true ) ) {
			return;
		}
		$this->messages[] = $message;

		$order->add_order_note( $message );

		$this->add_notice( $order, $notice_type, $message );
	}

	/**
	 * Adds an alma_refund_notices in post_meta
	 *
	 * @param \WC_Order $order The order where to add a note & notice.
	 * @param string    $notice_type Notice type.
	 * @param string    $message The message.
	 *
	 * @return void
	 */
	protected function add_notice( \WC_Order $order, $notice_type, $message ) {
		$refund_notices   = get_post_meta( $order->get_id(), Alma_Constants_Helper::REFUND_NOTICE_META_KEY );
		$refund_notices[] = array(
			'notice_type' => $notice_type,
			'message'     => $message,
		);
		update_post_meta( $order->get_id(), Alma_Constants_Helper::REFUND_NOTICE_META_KEY, $refund_notices );
	}

	/**
	 * Adds a refund error note to an order + a notice
	 *
	 * @param \WC_Order $order The order where to add a note & notice.
	 * @param string    $message The message.
	 *
	 * @return void
	 * @see add_order_note()
	 */
	public function add_error_note( \WC_Order $order, $message ) {
		$this->add_order_note( $order, Alma_Constants_Helper::ERROR, $message );
	}

	/**
	 * Tells if the order is valid for a partial refund.
	 *
	 * @param \WC_Order        $order An order.
	 * @param \WC_Order_Refund $refund A Refund object.
	 *
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
	 * Gets the amount to refund.
	 *
	 * @param \WC_Order_Refund $refund A Refund object.
	 *
	 * @return int
	 */
	public function get_refund_amount( $refund ) {
		return $this->tool_helper->alma_price_to_cents( floatval( $refund->get_amount() ) );
	}

	/**
	 * Tells if the order is valid for a full refund with Alma.
	 *
	 * @param \WC_Order $order An order.
	 *
	 * @return bool
	 */
	public function is_fully_refundable( $order ) {
		return $this->is_paid_with_alma( $order ) && $this->has_transaction_id( $order );
	}

	/**
	 * Has this order been paid via Alma payment method ?.
	 *
	 * @param \WC_Order $order An order.
	 *
	 * @return bool
	 */
	public function is_paid_with_alma( $order ) {
		return in_array(
			$order->get_payment_method(),
			array(
				Alma_Constants_Helper::GATEWAY_ID,
				Alma_Constants_Helper::ALMA_GATEWAY_PAY_LATER,
				Alma_Constants_Helper::ALMA_GATEWAY_PAY_MORE_THAN_FOUR,
				Alma_Constants_Helper::ALMA_GATEWAY_PAY_NOW,
			),
			true
		);
	}

	/**
	 * Has this order a transaction id ?.
	 *
	 * @param \WC_Order $order An order.
	 *
	 * @return bool
	 */
	protected function has_transaction_id( $order ) {
		if ( ! $order->get_transaction_id() ) {
			/* translators: %s is an order number. */
			$this->add_error_note( $order, sprintf( __( 'Error while getting alma transaction_id for order_id : %s.', 'alma-gateway-for-woocommerce' ), $order->get_id() ) );

			$this->logger->error(
				'Error while getting alma transaction_id from an order.',
				array(
					'Method'  => __METHOD__,
					'OrderId' => $order->get_id(),
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Print refund notices previously stored as HTML format
	 *
	 * @param \WC_Order $order The order linked to the refund notices.
	 *
	 * @return void
	 */
	public function print_notices( \WC_Order $order ) {
		$refund_notices = get_post_meta( $order->get_id(), Alma_Constants_Helper::REFUND_NOTICE_META_KEY, true );

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
	 * @param \WC_Order $order The order linked to the refund notices.
	 *
	 * @return void
	 */
	public function delete_notices( \WC_Order $order ) {
		delete_post_meta( $order->get_id(), Alma_Constants_Helper::REFUND_NOTICE_META_KEY );
	}
}
