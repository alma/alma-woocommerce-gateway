<?php
/**
 * Alma Admin Notes Legal Checkout helper
 *
 * @package Alma_WC_Helper_Admin_Check_Legal
 */


defined( 'ABSPATH' ) || exit;

/**
 * Alma_WC_Admin_Helper_Check_Legal
 */
class Alma_WC_Admin_Helper_Check_Legal {

	const ID = 'alma';

	/**
	 * Initialize our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'get_modal_checkout_legal' ) );

		wp_enqueue_style( 'alma-admin-styles-modal-checkout-legal', Alma_WC_Admin_Helper_General::get_asset_admin_url( 'css/alma-admin-modal-checkout-legal.css' ), array(), ALMA_WC_VERSION );

		wp_enqueue_script( 'alma-admin-scripts-modal-checkout-legal', Alma_WC_Admin_Helper_General::get_asset_admin_url( 'js/alma-admin-modal-checkout-legal.js' ), array(
			'jquery-effects-highlight',
			'jquery-ui-selectmenu'
		), ALMA_WC_VERSION, true );
	}

	/**
	 * @return bool
	 */
	public function get_modal_checkout_legal() {
		$title     = Alma_WC_Admin_Helper_Check_Legal::get_modal_title();
		$alma_logo = Alma_WC_Admin_Helper_General::get_icon( $this->get_modal_title(), Alma_WC_Admin_Helper_Check_Legal::ID );

		ob_start(); ?>

        <div class="notice notice-info" style=" display: flex;background-color:  #E9F4FF;">
            <div style="width: 86px;height: 50px;margin:15px;"> <?php echo $alma_logo; ?></div>
            <div style="flex-grow: 1;">
                <div>
                    <h2><?php echo $title; ?></h2>
                </div>
                <div>
                    <div>
                        <p>By accepting this option, enable Alma to analyse the usage of your payment
                            methods,<strong>get more informations to perform </strong>and share this data with you.
                            <br>You can <a href="mailto:support@getalma.eu">unsubscribe and erase your data</a> at any
                            moment.
                        </p>
                        <p>Know more about collected data</p>- total quantity of orders, amounts and currencies<br>-
                        payment provider for each order<br>
                    </div>
                </div>
                <div>
                    <div>
                        <a href="http://woocommerce-6-3-1.local.test/wp-admin/#"
                           class="components-button is-secondary">REJECT</a>
                        <a href="http://woocommerce-6-3-1.local.test/wp-admin/#"
                           class="components-button is-primary">ACCEPT</a>
                    </div>
                </div>
            </div>
        </div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * @return string|null
	 */
	public static function get_modal_title() {
		return __( 'Increase your performance & get insights !', 'alma-gateway-for-woocommerce' );
	}
}
