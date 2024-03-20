<?php
/**
 * VersionDeprecated.
 *
 * @since 4.3.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VersionDeprecated.
 */
class VersionDeprecated extends AlmaException {

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
