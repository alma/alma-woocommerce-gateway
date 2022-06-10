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
	private $refund_helper;

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
		$this->refund_helper           = new Alma_WC_Refund_Helper();
		$this->admin_texts_to_change   = array(
			'You will need to manually issue a refund through your payment gateway after using this.' => __( 'Refund will be operated directly with Alma.', 'alma-woocommerce-gateway' ),
			/* translators: %s is an amount with currency. */
			'Refund %s manually' => __( 'Refund %s with Alma', 'alma-woocommerce-gateway' ),
		);
		$this->number_of_texts_changed = 0;
	}

	/**
	 * Inits admin.
	 *
	 * @return void
	 */
	public function admin_init() {
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'woocommerce_order_partially_refunded' ), 10, 2 );
		add_action( 'woocommerce_order_fully_refunded', array( $this, 'woocommerce_order_fully_refunded' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 10 );
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'woocommerce_order_item_add_action_buttons' ), 10 );
		add_filter( 'gettext', array( $this, 'gettext' ), 10, 3 );
		add_filter( 'woocommerce_new_order_note_data', array( $this, 'woocommerce_new_order_note_data' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this->refund_helper, 'woocommerce_order_status_changed' ), 10, 3 );
	}

	/**
	 * Filters WC order note for order fully refunded by order state changed to "refunded"
	 *
	 * @param array $comment_datas Information values about the order note.
	 * @return array
	 */
	public function woocommerce_new_order_note_data( $comment_datas ) {

		if ( 'Order status set to refunded. To return funds to the customer you will need to issue a refund through your payment gateway.' === $comment_datas['comment_content'] ) {
			/* translators: %s is a username. */
			$comment_datas['comment_content'] = sprintf( __( 'Order fully refunded via Alma by %s.', 'alma-woocommerce-gateway' ), wp_get_current_user()->display_name );
		}

		return $comment_datas;
	}

	/**
	 * WP action hook "current_screen".
	 *
	 * @return void
	 */
	public function woocommerce_order_item_add_action_buttons() {
		if ( is_object( get_current_screen() ) && 'shop_order' === get_current_screen()->id ) {
			add_filter( 'gettext', array( $this, 'gettext' ), 10, 3 );
		}
	}

	/**
	 * Filters the text of the refund button on a back-office order page.
	 *
	 * @param string $translation A text translated.
	 * @param string $text A text to translate.
	 * @param string $domain A text domain.
	 * @return mixed|string
	 */
	public function gettext( $translation, $text, $domain ) {

		if ( 'woocommerce' !== $domain || ! array_key_exists( $text, $this->admin_texts_to_change ) || ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $translation;
		}

		foreach ( $this->admin_texts_to_change as $original_text => $updated_text ) {
			if ( $original_text === $text ) {
				$order_id = intval( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification
				$order    = wc_get_order( $order_id );
				if ( substr( $order->get_payment_method(), 0, 4 ) !== 'alma' ) {
					return $translation;
				}
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

		if ( 'shop_order' !== get_current_screen()->id ) {
			return;
		}

		global $post_id;
		$refund_notices = get_post_meta( $post_id, 'alma_refund_notices', true );

		if ( ! is_array( $refund_notices ) ) {
			return;
		}

		foreach ( $refund_notices as $notice_infos ) {
			if ( ! is_array( $notice_infos ) ) {
				continue;
			}
			echo '<div class="notice notice-' . esc_html( $notice_infos['notice_type'] ) . ' is-dismissible">
				<p>' . esc_html( $notice_infos['message'] ) . '</p>
			</div>';
		}
		delete_post_meta( $post_id, 'alma_refund_notices' );
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
		if ( ! $this->refund_helper->is_order_valid_for_partial_refund_with_alma( $order, $refund ) ) {
			return;
		}

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'Full refund unavailable due to a connection error.', 'alma-woocommerce-gateway' ) );
			return;
		}

		$amount_to_refund   = $this->refund_helper->get_amount_to_refund( $refund );
		$merchant_reference = $order->get_order_number();
		$refund_comment     = $this->refund_helper->get_refund_comment( $refund );

		try {
			$alma->payments->partialRefund( $order->get_transaction_id(), $amount_to_refund, $merchant_reference, $refund_comment );

			$refund = new WC_Order_Refund( $refund_id );
			/* translators: %1$s is a username, %2$s is an amount with currency. */
			$this->refund_helper->add_order_note( $order_id, 'success', sprintf( __( '%1$s refunded %2$s with Alma.', 'alma-woocommerce-gateway' ), wp_get_current_user()->display_name, $this->refund_helper->get_amount_to_refund_for_display( $refund ) ) );
		} catch ( RequestError $e ) {
			/* translators: %s is an error message. */
			$error_message = sprintf( __( 'Alma partial refund error : %s.', 'alma-woocommerce-gateway' ), alma_wc_get_request_error_message( $e ) );
			$this->refund_helper->add_order_note( $order_id, 'error', $error_message );
			$this->logger->error( $error_message );
		}
	}

	/**
	 * Action hook for order fully refunded.
	 *
	 * @param integer $order_id Order id.
	 * @return void
	 */
	public function woocommerce_order_fully_refunded( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( true !== $this->refund_helper->is_order_valid_for_full_refund_with_alma( $order ) ) {
			return;
		}

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'API client init error.', 'alma-woocommerce-gateway' ) );
			return;
		}

		$merchant_reference = $order->get_order_number();

		try {
			/* translators: %s is a username. */
			$refund_comment = sprintf( __( 'Full refund made via WooCommerce back-office by %s.', 'alma-woocommerce-gateway' ), wp_get_current_user()->display_name );
			$alma->payments->fullRefund( $order->get_transaction_id(), $merchant_reference, $refund_comment );
			$this->refund_helper->add_order_note( $order_id, 'success', $refund_comment );

		} catch ( RequestError $e ) {
			/* translators: %s is an error message. */
			$error_message = sprintf( __( 'Alma full refund error : %s.', 'alma-woocommerce-gateway' ), $e->getMessage() );
			$this->refund_helper->add_order_note( $order_id, 'error', $error_message );
			$this->logger->error( $error_message );
		}
	}

}

