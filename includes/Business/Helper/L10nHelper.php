<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\WooCommerce\Proxy\HooksProxy;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

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
	 * @sonar It's a convention to use __() for translations
	 * @phpcs We pass a variable to __() call because it's a proxy!
	 */
	public static function __( string $translation, string $domain = self::ALMA_L10N_DOMAIN ): string /* NOSONAR */ {
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
