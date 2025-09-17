<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;

class RequirementsHelper {

	/**
	 * Check if we met dependencies.
	 *
	 * @param string $cmsVersion The current version of WooCommerce
	 *
	 * @return true
	 * @throws RequirementsHelperException
	 */
	public function check_dependencies( string $cmsVersion ): bool {
		if ( ! function_exists( 'WC' ) ) {
			throw new RequirementsHelperException( L10nHelper::__( 'Alma requires WooCommerce to be activated' ) );
		}

		if ( version_compare( $cmsVersion, '7.0.0', '<' ) ) {
			throw new RequirementsHelperException(
				L10nHelper::__( 'Alma requires WooCommerce version 7.0.0 or greater' )
			);
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new RequirementsHelperException(
				L10nHelper::__( 'Alma requires the cURL PHP extension to be installed on your server' )
			);
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new RequirementsHelperException(
				L10nHelper::__( 'Alma requires the JSON PHP extension to be installed on your server' )
			);
		}

		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new RequirementsHelperException( L10nHelper::__( 'Alma requires OpenSSL to be installed on your server' ) );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new RequirementsHelperException( L10nHelper::__( 'Alma requires OpenSSL to be installed on your server' ) );
		}

		return true;
	}
}
