<?php
/**
 * WrongCredentialsException.
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
 * WrongCredentialsException
 */
class WrongCredentialsException extends AlmaException {


	/**
	 * Constructor.
	 *
	 * @param string $environment The environment.
	 */
	public function __construct( $environment ) {
		$message = sprintf(
		// translators: %s: Alma dashboard url.
			__( 'Could not connect to Alma using your API keys.<br>Please double check your keys on your <a href="%1$s" target="_blank">Alma dashboard</a>.', 'alma-gateway-for-woocommerce' ),
			AssetsHelper::get_alma_dashboard_url( $environment, 'security' )
		);

		parent::__construct( $message );
	}

}
