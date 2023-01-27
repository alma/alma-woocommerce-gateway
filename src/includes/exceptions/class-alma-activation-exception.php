<?php
/**
 * Alma_Activation_Exception.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;

/**
 * Alma_Activation_Exception.
 */
class Alma_Activation_Exception extends Alma_Exception {

	/**
	 * Constructor.
	 *
	 * @param string $environment The environment.
	 */
	public function __construct( $environment ) {
		$message = sprintf( // translators: %s: Alma dashboard url.
			__( 'Your Alma account needs to be activated before you can use Alma on your shop.<br>Go to your <a href="%1$s" target="_blank">Alma dashboard</a> to activate your account.<br><a href="%2$s">Refresh</a> the page when ready.', 'alma-gateway-for-woocommerce' ),
			Alma_Assets_Helper::get_alma_dashboard_url( $environment, 'settings' ),
			esc_url( Alma_Assets_Helper::get_admin_setting_url() )
		);

		parent::__construct( $message );
	}
}
