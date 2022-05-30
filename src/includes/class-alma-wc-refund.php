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
	 * @var Alma_WC_Share_Of_Checkout_Helper
	 */
	private $refund_helper;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger        = new Alma_WC_Logger();
		$this->refund_helper = new Alma_WC_Refund_Helper();
	}

	/**
	 * Init.
	 */
	public function init() {
		error_log( 'public function init()' );
		error_log( json_encode( $_POST ) );
		if ( isset( $_POST ) && isset( $_POST['alma_refund_hidden'] ) ) {
			$this->do_refund( intval( $_POST['post_ID'] ) );
		}
		add_action( 'add_meta_boxes', array( $this, 'add_refund_meta_box' ) );
	}

	/**
	 * Does refund.
	 *
	 * @param $order_id Integer The order id.
	 * @return void
	 * @throws Alma_WC_Payment_Validation_Error
	 */
	public function do_refund( $order_id ) {

		$order = wc_get_order( $order_id );
//        dd($order);

		if ( ! $order->get_transaction_id() ) {
			$this->logger->error( 'Error while getting transaction_id on trigger_payment for order_id = ' . $order_id );
			return;
		}

		error_log( '$payment_id' );
		error_log( $order->get_transaction_id() );

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			throw new Alma_WC_Payment_Validation_Error( 'api_client_init' );
		}

		try {
			$alma_model_order = new Alma_WC_Model_Order( $order_id );
		} catch ( Exception $e ) {
			$logger = new Alma_WC_Logger();
			$logger->error( 'Error getting payment info from order: ' . $e->getMessage() );
			return;
		}

		$merchant_reference = $alma_model_order->get_order_reference();
		error_log( '$merchant_reference' );
		error_log( $merchant_reference );

		$amout_to_refund = alma_wc_price_to_cents( floatval( $_POST['alma_refund_amount'] ) );
		error_log( '$amout_to_refund' );
		error_log( $amout_to_refund );

		if ( 'partial' === $_POST['refund_type'] ) {
			$alma->payments->partialRefund( $order->get_transaction_id(), $amout_to_refund, $merchant_reference );
		} elseif ( 'full' === $_POST['refund_type'] ) {
			$alma->payments->fullRefund( $order->get_transaction_id(), $amout_to_refund, $merchant_reference );
		}

	}

	/**
	 * Adds the refund meta_box on shop_order page on back-office.
	 *
	 * @return void
	 */
	public function add_refund_meta_box() {
		add_meta_box( 'alma_refund_meta_box', __( 'Alma Refund', 'alma-woocommerce-gateway' ), array( $this, 'display_refund_meta_box' ), 'shop_order', 'normal', 'default' );
	}

	/**
	 * Displays refund form in meta_box.
	 *
	 * @return void
	 */
	public function display_refund_meta_box() {

		$order = wc_get_order( intval( $_GET['post'] ) );

		$max_amount_refund = $order->get_total();

		$currency_refund   = '€';

		?>
		<style>#alma-payment-plans button {background-color:white;}</style>
		<div id="meta_box_alma_refund" style="">
			<?php
			echo '<p>' . __( 'Refund this command via Alma ... Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent quis accumsan eros. Phasellus varius sapien a sapien sodales hendrerit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 'alma-woocommerce-gateway' ) . '</p>';
			?>
			<form id="form_alma_refund" method="post" action="?">
				<input type="hidden" name="alma_refund_hidden" value="1">
				<input type="hidden" name="alma_refund_currency" value="<?php echo esc_attr( $currency_refund ); ?>">

				<span><?php _e( 'Refund type', 'alma-woocommerce-gateway' ); ?></span><br />

				<label for="alma_refund_full"><?php _e( 'Total amount', 'alma-woocommerce-gateway' ); ?></label>
				<input type="radio" name="refund_type" id="alma_refund_full" value="full">

				<label for="alma_refund_partial"><?php _e( 'Partial', 'alma-woocommerce-gateway' ); ?></label>
				<input type="radio" name="refund_type" id="alma_refund_partial" value="partial" checked><br />

                <?php
                echo sprintf( 'Amount to refund (Max : %s %s)', $max_amount_refund, $currency_refund );
                ?>
                <input type="number" step="0.01" min="0.01" max="<?php echo esc_attr( $max_amount_refund ); ?>" name="alma_refund_amount" id="alma_refund_amount" value="4" />
                <br />

				<input type="submit" value="<?php esc_attr_e( 'Process refund', 'alma-woocommerce-gateway' ); ?>" class="button button-primary" />
			</form>
		</div>
		<?php
	}

}



























