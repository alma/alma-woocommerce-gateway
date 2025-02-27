<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\WooCommerce\Proxy\HooksProxy;

class L10nHelper {

	const ALMA_L10N_DOMAIN = 'alma-gateway-for-woocommerce';

	/**
	 * Translate a string.
	 * The function name is deliberately kept short for simplicity.
	 *
	 * @param string $translation
	 * @param string $domain
	 *
	 * @return string
	 */
	public static function __( $translation, $domain = self::ALMA_L10N_DOMAIN ) /* NOSONAR */ {
		return __( $translation, $domain );// phpcs:ignore
	}

	/**
	 * Load the plugin language files.
	 *
	 * @param $language_path
	 *
	 * @return void
	 */
	public static function load_language( $language_path ) {
		HooksProxy::load_language( self::ALMA_L10N_DOMAIN, $language_path );
	}
}
