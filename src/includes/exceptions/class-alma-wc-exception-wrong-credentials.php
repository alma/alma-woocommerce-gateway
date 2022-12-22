<?php
/**
 * Alma_WC_Exception_Wrong_Credentials.
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
 * Alma_WC_Exception_Wrong_Credentials
 */
class Alma_WC_Exception_Wrong_Credentials extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $environment The environment.
	 */
	public function __construct( $environment ) {
		$message = sprintf(
		// translators: %s: Alma dashboard url.
			__( 'Could not connect to Alma using your API keys.<br>Please double check your keys on your <a href="%1$s" target="_blank">Alma dashboard</a>.', 'alma-gateway-for-woocommerce' ),
			Alma_WC_Helper_Assets::get_alma_dashboard_url( $environment, 'security' )
		);

		parent::__construct( $message );
	}

}
