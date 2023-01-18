<?php
/**
 * Alma_Wrong_Credentials.
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

use Alma\Woocommerce\Helpers\Alma_Assets;

/**
 * Alma_Wrong_Credentials
 */
class Alma_Wrong_Credentials extends \Exception {


	/**
	 * Constructor.
	 *
	 * @param string $environment The environment.
	 */
	public function __construct( $environment ) {
		$message = sprintf(
		// translators: %s: Alma dashboard url.
			__( 'Could not connect to Alma using your API keys.<br>Please double check your keys on your <a href="%1$s" target="_blank">Alma dashboard</a>.', 'alma-gateway-for-woocommerce' ),
			Alma_Assets::get_alma_dashboard_url( $environment, 'security' )
		);

		parent::__construct( $message );
	}

}
