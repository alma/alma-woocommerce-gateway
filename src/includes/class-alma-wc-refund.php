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
	 * init.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_refund_meta_box' ) );
	}

	/**
	 * Adds the refund meta_box on shop_order page on back-office.
	 *
	 * @return void
	 */
	public function add_refund_meta_box() {
		add_meta_box( 'id_refund_meta_box', __( 'Alma Refund', 'alma-woocommerce-gateway' ), array( $this, 'display_refund_meta_box' ) );
//		add_meta_box( 'id_refund_meta_box', __( 'My Field', 'alma-woocommerce-gateway' ), 'display_refund_meta_box', 'shop_order', 'normal', 'default' );
	}

	/**
	 * Displays refund form in meta_box.
	 *
	 * @return void
	 */
	public function display_refund_meta_box() {

        $max_amount_refund = '1204.65';
        $currency_refund   = '€';

		?>
		<style>#alma-payment-plans button {background-color:white;}</style>
		<div id="meta_box_alma_refund" style="">
			<?php
			echo '<p>' . __( 'Refund this command via Alma ... Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent quis accumsan eros. Phasellus varius sapien a sapien sodales hendrerit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 'alma-woocommerce-gateway' ) . '</p>';
			?>
			<form id="form_alma_refund" method="post" action="?">
				<span><?php _e( 'Refund type', 'alma-woocommerce-gateway' ) ?></span><br />

				<label for="alma_refund_full"><?php _e( 'Total amount', 'alma-woocommerce-gateway' ) ?></label>
				<input type="radio" name="refund_type[]" id="alma_refund_full" value="full" checked>

				<label for="alma_refund_partial"><?php _e( 'Partial', 'alma-woocommerce-gateway' ) ?></label>
				<input type="radio" name="refund_type[]" id="alma_refund_partial" value="partial"><br />

				<label for="alma_refund_amount"><?php _e( 'Partial', 'alma-woocommerce-gateway' ) ?>
					<?php
                    echo sprintf( 'Amount to refund (Max : %s %s)', $max_amount_refund, $currency_refund );
                    ?>
                    <input type="number" step="0.01" min="0.01" max="<?php echo esc_attr( $max_amount_refund ); ?>" name="alma_refund_amount" id="alma_refund_amount" />
				</label><br />

                <input type="button" value="<?php esc_attr_e( 'Process refund', 'alma-woocommerce-gateway' ) ?>" class="button button-primary" />
			</form>
		</div>
		<?php
	}

}




































