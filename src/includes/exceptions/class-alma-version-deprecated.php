<?php
/**
 * Alma_Version_Deprecated.
 *
 * @since 4.3.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Version_Deprecated.
 */
class Alma_Version_Deprecated extends Alma_Exception {

	/**
	 * Constructor.
	 *
	 * @param string $alma_version The alma version.
	 */
	public function __construct( $alma_version ) {
		$message = sprintf( // translators: %s: Alma dashboard url.
			__( 'Before installing this version of the Alma plugin, you need to manually remove the old version "%s", then deactivate and reactivate the new version', 'alma-gateway-for-woocommerce' ),
			$alma_version
		);

		parent::__construct( $message );
	}
}
