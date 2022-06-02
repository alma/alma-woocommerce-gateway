<?php
/**
 * Alma refund
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Refund
 */
class Alma_WC_Refund {

	const FOO = 'bar';

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
	 */
	private $admin_texts_to_change;

	/**
	 * Number of admin texts dynamically changed.
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
		add_action( 'woocommerce_order_fully_refunded', array( $this, 'woocommerce_order_fully_refunded' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 10 );
		add_action( 'admin_body_class', array( $this, 'admin_body_class' ), 10, 1 );
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'woocommerce_order_item_add_action_buttons' ), 10 );
		add_filter( 'gettext', array( $this, 'gettext' ), 10, 3 );
		add_action( 'woocommerce_order_status_changed', array( $this->refund_helper, 'woocommerce_order_status_changed' ), 10, 3 );
	}

	/**
	 * WP action hook "current_screen".
	 *
	 * @return void
	 */
	public function woocommerce_order_item_add_action_buttons() {
		if ( 'shop_order' === get_current_screen()->id ) {
			add_filter( 'gettext', array( $this, 'gettext' ), 10, 3 );
		}
	}

	/**
	 * Filters the text of the refund button on a back-office order page.
	 *
	 * @param $translation String A text translated.
	 * @param $text String A text to translate.
	 * @param $domain String A text domain.
	 * @return mixed|string
	 */
	public function gettext( $translation, $text, $domain ) {

		if ( 'woocommerce' !== $domain || ! array_key_exists( $text, $this->admin_texts_to_change ) ) {
			return $translation;
		}

		foreach ( $this->admin_texts_to_change as $original_text => $updated_text ) {
			if ( $original_text === $text ) {
				$order = wc_get_order( intval( $_GET['post'] ) );
				if ( substr( $order->get_payment_method(), 0, 4 ) !== 'alma' ) {
					return $translation;
				}
				$translation = str_replace( $original_text, $updated_text, $text );
				$this->number_of_texts_changed++;
			}
		}

		if ( $this->number_of_texts_changed === count( $this->admin_texts_to_change ) ) {
			remove_filter( 'gettext', array( $this, 'gettext' ), 10 );
		}

		return $translation;
	}

	/**
	 * Adds a class in the admin <body>.
	 *
	 * @param $classes String List of classes already loaded by WP, WC, and other plugins and theme.
	 * @return string|void
	 * @deprecated ?
	 */
	public function admin_body_class( $classes ) {

		if ( 'shop_order' !== get_current_screen()->id ) {
			return;
		}

		global $post_id;
		$order = wc_get_order( $post_id );
		if ( substr( $order->get_payment_method(), 0, 4 ) !== 'alma' ) {
			return;
		}

		$classes .= ' shop_order_payment_method_alma';
		return $classes;
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
			?>
			<div class="notice notice-<?php echo $notice_infos['notice_type']; ?> is-dismissible">
				<p><?php echo $notice_infos['message']; ?></p>
			</div>
			<?php
		}
		delete_post_meta( $post_id, 'alma_refund_notices' );
	}

	/**
	 * Action hook for order partial refunded.
	 *
	 * @param $order_id Integer Order id.
	 * @param $refund_id Integer Refund id.
	 * @return void
	 */
	public function woocommerce_order_partially_refunded( $order_id, $refund_id ) {
		$order = wc_get_order( $order_id );

		if ( substr( $order->get_payment_method(), 0, 4 ) !== 'alma' ) {
			return;
		}

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'API client init error.', 'alma-woocommerce-gateway' ) );
			return;
		}

		if ( ! $order->get_transaction_id() ) {
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'Error while getting transaction_id on trigger_payment for order_id = ' . $order_id, 'alma-woocommerce-gateway' ) );
			$this->logger->error( 'Error while getting transaction_id on trigger_payment for order_id = ' . $order_id );
			return;
		}

		$amount_to_refund = $this->refund_helper->get_amount_to_refund( $refund_id );
		if ( 0 === $amount_to_refund ) {
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'Amount canno\'t be equal to 0 to refund with Alma.', 'alma-woocommerce-gateway' ) );
			return;
		}

		$merchant_reference = $this->refund_helper->get_merchant_reference( $order_id );
		if ( null === $merchant_reference ) {
			/* translators: %s is an order number. */
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'Alma partial refund error : merchant reference is missing.', 'alma-woocommerce-gateway' ) );
			$this->logger->error( sprintf( __( 'Partial refund error : merchant reference is missing for order number %s.', 'alma-woocommerce-gateway' ), $order_id ) );
			return;
		}

		$refund_comment = $this->refund_helper->get_refund_comment( $refund_id );

		try {
			$alma->payments->partialRefund( $order->get_transaction_id(), $amount_to_refund, $merchant_reference, $refund_comment );
		} catch ( Exception $e ) {
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'Alma partial refund error.', 'alma-woocommerce-gateway' ) );
			$this->logger->error( 'Error partialRefund : ' . $e->getMessage() );
			return;
		}

		/* translators: %1$s is a user name, %2$s is an amount with currency. */
		$this->refund_helper->add_order_note( $order_id, 'success', sprintf( __( '%1$s refunded %2$s via Alma.', 'alma-woocommerce-gateway' ), wp_get_current_user()->display_name, $this->refund_helper->get_amount_to_refund( $refund_id, true ) ) );
	}

	/**
	 * Action hook for order fully refunded.
	 *
	 * @param $order_id Integer Order id.
	 * @param $refund_id Integer Refund id.
	 * @return void
	 */
	public function woocommerce_order_fully_refunded( $order_id, $refund_id ) {

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			$this->refund_helper->add_order_note( $order_id, 'error', __( 'API client init error.', 'alma-woocommerce-gateway' ) );
			return;
		}

		$order = wc_get_order( $order_id );

		if ( 'alma' !== substr( $order->get_payment_method(), 0, 4 ) ) {
			return;
		}

		if ( ! $order->get_transaction_id() ) {
			$message = sprintf( __( 'Error while getting transaction_id for order_id = %s.', 'alma-woocommerce-gateway' ), $order_id );
			$this->refund_helper->add_order_note( $order_id, 'error', $message );
			$this->logger->error( $message );
			return;
		}

		$merchant_reference = $this->refund_helper->get_merchant_reference( $order_id );
		if ( null === $merchant_reference ) {
			/* translators: %s is an order number. */
			$message = sprintf( __( 'Full refund error : merchant reference is missing for order number %s.', 'alma-woocommerce-gateway' ), $order_id );
			$this->refund_helper->add_order_note( $order_id, 'error', $message );
			$this->logger->error( $message );
			return;
		}

		$refund_comment = $this->refund_helper->get_refund_comment( $refund_id );

		try {
			$alma->payments->fullRefund( $order->get_transaction_id(), $merchant_reference, $refund_comment );
		} catch ( Exception $e ) {
			$this->refund_helper->add_order_note( $order_id, 'error', $e->getMessage() );
			$this->logger->error( 'Error fullRefund : ' . $e->getMessage() );
			return;
		}

		$this->refund_helper->add_order_note( $order_id, 'success', __( 'Alma full refund success.', 'alma-woocommerce-gateway' ) );

	}

}

