<?php
/**
 * Alma_WC_Exception_Alma_Activation.
 *
 * @since 4.0.0
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/includes/exceptions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_WC_Exception_Alma_Activation.
 */
class Alma_WC_Exception_Alma_Activation extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $environment The environment.
	 */
	public function __construct( $environment ) {
		$message = sprintf( // translators: %s: Alma dashboard url.
			__( 'Your Alma account needs to be activated before you can use Alma on your shop.<br>Go to your <a href="%1$s" target="_blank">Alma dashboard</a> to activate your account.<br><a href="%2$s">Refresh</a> the page when ready.', 'alma-gateway-for-woocommerce' ),
			Alma_WC_Helper_Assets::get_alma_dashboard_url( $environment, 'settings' ),
			esc_url( Alma_WC_Helper_Assets::get_admin_setting_url() )
		);

		parent::__construct( $message );
	}
}
