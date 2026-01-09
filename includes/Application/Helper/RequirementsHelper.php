<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;

class RequirementsHelper {

	const MIN_WOOCOMMERCE_VERSION = '8.2.0';

	/**
	 * Check if we met dependencies.
	 *
	 * @param string $cmsVersion The current version of WooCommerce
	 *
	 * @return true
	 * @throws RequirementsHelperException
	 */
	public static function check_dependencies( string $cmsVersion ): bool {
		if ( ! function_exists( 'WC' ) ) {
			throw new RequirementsHelperException( 'Alma requires WooCommerce to be activated' );
		}

		if ( version_compare( $cmsVersion, self::MIN_WOOCOMMERCE_VERSION, '<' ) ) {
			throw new RequirementsHelperException(
				sprintf(
					'Alma requires WooCommerce version %s or greater',
					self::MIN_WOOCOMMERCE_VERSION
				)
			);
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new RequirementsHelperException( 'Alma requires the cURL PHP extension to be installed on your server' );
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new RequirementsHelperException( 'Alma requires the JSON PHP extension to be installed on your server' );
		}

		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new RequirementsHelperException( 'Alma requires OpenSSL to be installed on your server' );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new RequirementsHelperException( 'Alma requires OpenSSL to be installed on your server' );
		}

		return true;
	}
}
