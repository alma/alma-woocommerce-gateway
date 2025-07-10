<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\Business\Exception\RequirementsException;
use Alma\Gateway\Business\Service\WooCommerceService;

class RequirementsHelper {

	/**
	 * Check if we met dependencies.
	 *
	 * @return true
	 * @throws RequirementsException
	 */
	public function check_dependencies(): bool {
		if ( ! function_exists( 'WC' ) ) {
			throw new RequirementsException( L10nHelper::__( 'Alma requires WooCommerce to be activated' ) );
		}

		if ( version_compare( WooCommerceService::get_version(), '3.0.0', '<' ) ) {
			throw new RequirementsException(
				L10nHelper::__( 'Alma requires WooCommerce version 3.0.0 or greater' )
			);
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new RequirementsException(
				L10nHelper::__( 'Alma requires the cURL PHP extension to be installed on your server' )
			);
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new RequirementsException(
				L10nHelper::__( 'Alma requires the JSON PHP extension to be installed on your server' )
			);
		}

		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new RequirementsException( L10nHelper::__( 'Alma requires OpenSSL to be installed on your server' ) );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new RequirementsException( L10nHelper::__( 'Alma requires OpenSSL to be installed on your server' ) );
		}

		return true;
	}
}
