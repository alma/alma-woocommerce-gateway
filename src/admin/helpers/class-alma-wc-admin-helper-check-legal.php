<?php
/**
 * Alma Admin Notes Legal Checkout helper
 *
 * @package Alma_WC_Helper_Admin_Check_Legal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alma_WC_Admin_Helper_Check_Legal
 *
 * Display the legal modal checkout
 */
class Alma_WC_Admin_Helper_Check_Legal {

	const ID = 'alma';

	/**
	 * Initialize our hooks.
	 * Display legal notice checkout
	 * Includes the required assets
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'get_modal_checkout_legal' ) );

		wp_enqueue_style( 'alma-admin-styles-modal-checkout-legal', Alma_WC_Admin_Helper_General::get_asset_admin_url( 'css/alma-admin-modal-checkout-legal.css' ), array(), ALMA_WC_VERSION );

		wp_enqueue_script(
			'alma-admin-scripts-modal-checkout-legal',
			Alma_WC_Admin_Helper_General::get_asset_admin_url( 'js/alma-admin-modal-checkout-legal.js' ),
			array(
				'jquery-effects-highlight',
				'jquery-ui-selectmenu',
			),
			ALMA_WC_VERSION,
			true
		);
	}

	/**
	 * Build and display the modal
	 *
	 * @return void
	 */
	public function get_modal_checkout_legal() {
		$title     = self::get_modal_title();
		$alma_logo = Alma_WC_Admin_Helper_General::get_icon( $this->get_modal_title(), self::ID );

		ob_start(); ?>

		<div class="notice notice-info notice-modal-checkout-legal">
			<div class="modal-checkout-legal-logo"> <?php echo $alma_logo; ?></div>
			<div>
				<div>
					<h1><?php echo $title; ?></h1>
				</div>
				<div>
					<p>
						<?php
						echo __(
							'By accepting this option, enable Alma to analyse the usage of your payment
                        methods,<strong>get more informations to perform </strong>and share this data with you.',
							'alma-gateway-for-woocommerce'
						);
						?>
						<br>
						<?php
						echo __(
							'You can <a href="mailto:support@getalma.eu">unsubscribe and erase your data</a> at any
                        moment.',
							'alma-gateway-for-woocommerce'
						);
						?>
					</p>
					<p class="legal-checkout-collapsible">
						<?php echo __( 'Know more about collected data', 'alma-gateway-for-woocommerce' ); ?>
						<span id="legal-collapse-chevron" class="legal-checkout-chevron bottom"></span>
					</p>
					<ul class="legal-checkout-content">
						<li> <?php echo __( 'total quantity of orders, amounts and currencies', 'alma-gateway-for-woocommerce' ); ?></li>
						r/squizlabs/php_codesniffer/bin/phpcbf
						<li> <?php echo __( 'payment provider for each order', 'alma-gateway-for-woocommerce' ); ?></li>
					</ul>
				</div>
				<div id="legal-checkout-choices">
					<a href="http://woocommerce-6-3-1.local.test/wp-admin/#"
					   class="button-checkout-legal reject-legal"><?php echo __( 'REJECT', 'alma-gateway-for-woocommerce' ); ?></a>
					<a href="http://woocommerce-6-3-1.local.test/wp-admin/#"
					   class="button-checkout-legal accept-legal"><?php echo __( 'ACCEPT', 'alma-gateway-for-woocommerce' ); ?></a>

				</div>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Get the modal title
	 *
	 * @return string
	 */
	public static function get_modal_title() {
		return __( 'Increase your performance & get insights !', 'alma-gateway-for-woocommerce' );
	}


}
