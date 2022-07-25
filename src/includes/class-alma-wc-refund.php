<?php
/**
 * Alma refund
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Refund
 */
class Alma_WC_Refund {

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Logger
	 *
	 * @var Alma_WC_Refund_Helper
	 */
	private $helper;

	/**
	 * Admin texts to be changed in order page to replace by Alma text.
	 *
	 * @var array
	 */
	private $admin_texts_to_change;

	/**
	 * Number of admin texts dynamically changed.
	 *
	 * @var integer
	 */
	private $number_of_texts_changed;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger                  = new Alma_WC_Logger();
		$this->helper                  = new Alma_WC_Refund_Helper();
		$this->admin_texts_to_change   = array(
			'You will need to manually issue a refund through your payment gateway after using this.' => __( 'Refund will be operated directly with Alma.', 'alma-gateway-for-woocommerce' ),
			/* translators: %s is an amount with currency. */
			'Refund %s manually' => __( 'Refund %s with Alma', 'alma-gateway-for-woocommerce' ),
		);
		$this->number_of_texts_changed = 0;
	}

	/**
	 * Inits admin.
	 *
	 * @return void
	 */
	public function admin_init() {
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$maybe_wc_order = wc_get_order( intval( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $maybe_wc_order instanceof WC_order && $this->helper->is_paid_with_alma( $maybe_wc_order ) ) {
				add_action( 'admin_notices', array( $this, 'admin_notices' ), 10 );
				add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'woocommerce_order_item_add_action_buttons' ), 10 );
			}
		}
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'woocommerce_order_partially_refunded' ), 10, 2 );
		add_action( 'woocommerce_order_fully_refunded', array( $this, 'woocommerce_order_fully_refunded' ), 10, 2 );
		add_filter( 'woocommerce_new_order_note_data', array( $this, 'woocommerce_new_order_note_data' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'woocommerce_order_status_changed' ), 10, 3 );
	}

	/**
	 * Callback function for the event "order status changed".
	 * This method will make full refund if possible.
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
			true === $this->helper->is_fully_refundable( $order )
		) {
			$this->helper->make_full_refund( $order );
		}
	}


	/**
	 * Filters WC order note for order fully refunded by order state changed to "refunded".
	 *
	 * @see https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-order.html#source-view.1724
	 * @param array $comment_datas Information values about the order note.
	 * @return array
	 */
	public function woocommerce_new_order_note_data( $comment_datas ) {
		$wc_order = wc_get_order( $comment_datas['comment_post_ID'] );
		if ( $this->helper->is_fully_refundable( $wc_order ) && 'Order status set to refunded. To return funds to the customer you will need to issue a refund through your payment gateway.' === $comment_datas['comment_content'] ) {
			/* translators: %s is a username. */
			$comment_datas['comment_content'] = sprintf( __( 'Order fully refunded via Alma by %s.', 'alma-gateway-for-woocommerce' ), wp_get_current_user()->display_name );
		}

		return $comment_datas;
	}

	/**
	 * Adds a filter on "gettext" in the "woocommerce_order_item_add_action_buttons" action hook.
	 *
	 * @return void
	 */
	public function woocommerce_order_item_add_action_buttons() {
		add_filter( 'gettext', array( $this, 'gettext' ), 10, 3 );
	}

	/**
	 * Filters the text of the refund button on a back-office order page.
	 *
	 * @param string $translation A text translated.
	 * @param string $text A text to translate.
	 * @param string $domain A text domain.
	 * @return string
	 */
	public function gettext( $translation, $text, $domain ) {
		if ( 'woocommerce' !== $domain || ! array_key_exists( $text, $this->admin_texts_to_change ) || ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $translation;
		}

		foreach ( $this->admin_texts_to_change as $original_text => $updated_text ) {
			if ( $original_text === $text ) {
				$translation = str_replace( $original_text, $updated_text, $text );
				$this->number_of_texts_changed++;
			}
		}

		if ( count( $this->admin_texts_to_change ) === $this->number_of_texts_changed ) {
			remove_filter( 'gettext', array( $this, 'gettext' ), 10 );
		}

		return $translation;
	}

	/**
	 * Print refund notices.
	 *
	 * @return void
	 */
	public function admin_notices() {
		$order = wc_get_order();
		if ( $order ) {
			$this->helper->print_notices( $order );
			$this->helper->delete_notices( $order );
		}
	}

	/**
	 * Action hook for order partial refunded.
	 *
	 * @param integer $order_id Order id.
	 * @param integer $refund_id Refund id.
	 * @return void
	 */
	public function woocommerce_order_partially_refunded( $order_id, $refund_id ) {
		$order  = wc_get_order( $order_id );
		$refund = new WC_Order_Refund( $refund_id );
		if ( ! $this->helper->is_partially_refundable( $order, $refund ) ) {
			return;
		}

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			$this->helper->add_error_note( $order, __( 'Partial refund unavailable due to a connection error.', 'alma-gateway-for-woocommerce' ) );
			return;
		}

		$amount_to_refund   = $this->helper->get_refund_amount( $refund );
		$merchant_reference = $order->get_order_number();
		$comment            = $this->helper->format_refund_comment( $refund->get_reason() );
		try {
			$alma->payments->partialRefund( $order->get_transaction_id(), $amount_to_refund, $merchant_reference, $comment );

			$refund = new WC_Order_Refund( $refund_id );
			$this->helper->add_success_note(
				$order,
				sprintf(
					/* translators: %1$s is a username, %2$s is an amount with currency. */
					__( '%1$s refunded %2$s with Alma.', 'alma-gateway-for-woocommerce' ),
					wp_get_current_user()->display_name,
					$this->helper->get_display_refund_amount( $refund )
				)
			);
		} catch ( RequestError $e ) {
			/* translators: %s is an error message. */
			$error_message = sprintf( __( 'Alma partial refund error : %s.', 'alma-gateway-for-woocommerce' ), $e->getErrorMessage() );
			$this->helper->add_error_note( $order, $error_message );
			$this->logger->error( $error_message );
		}
	}

	/**
	 * Action hook for order fully refunded.
	 *
	 * @param integer $order_id Order id.
	 * @param integer $refund_id Refund id.
	 * @return void
	 */
	public function woocommerce_order_fully_refunded( $order_id, $refund_id ) {
		$order = wc_get_order( $order_id );
		if (
			'refunded' === $order->get_status() &&
			true === $this->helper->is_fully_refundable( $order )
		) {
			$this->helper->make_full_refund( $order, $refund_id );
		}
	}

}
