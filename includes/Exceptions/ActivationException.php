<?php
/**
 * ActivationException.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Alma\Woocommerce\Helpers\AssetsHelper;

/**
 * ActivationException.
 */
class ActivationException extends AlmaException {

	/**
	 * Constructor.
	 *
	 * @param string $environment The environment.
	 */
	public function __construct( $environment ) {
		$asset_helper = new AssetsHelper();

		$message = sprintf( // translators: %s: Alma dashboard url.
			__( 'Your Alma account needs to be activated before you can use Alma on your shop.<br>Go to your <a href="%1$s" target="_blank">Alma dashboard</a> to activate your account.<br><a href="%2$s">Refresh</a> the page when ready.', 'alma-gateway-for-woocommerce' ),
			AssetsHelper::get_alma_dashboard_url( $environment, 'settings' ),
			esc_url( $asset_helper->get_admin_setting_url() )
		);

		parent::__construct( $message );
	}
}
