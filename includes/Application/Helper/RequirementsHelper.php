<?php

namespace Alma\Gateway\Application\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;

class RequirementsHelper {

	const MIN_WOOCOMMERCE_VERSION = '10.1.0';
	const MIN_WORDPRESS_VERSION = '6.6';

	/**
	 * Check if we met requirements.
	 *
	 * @return true
	 * @throws RequirementsHelperException
	 */
	public static function check_requirements(): bool {

		if ( ! function_exists( 'curl_init' ) ) {
			throw new RequirementsHelperException(
				__( 'Alma requires the cURL PHP extension to be installed on your server' )
			);
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new RequirementsHelperException(
				__( 'Alma requires the JSON PHP extension to be installed on your server' )
			);
		}

		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new RequirementsHelperException(
				__( 'Alma requires OpenSSL to be installed on your server' )
			);
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new RequirementsHelperException(
				__( 'Alma requires OpenSSL to be installed on your server' )
			);
		}

		return true;
	}

	/**
	 * Check if we met dependencies.
	 *
	 * @param string $platformVersion The current version of WordPress
	 * @param string $cmsVersion The current version of WooCommerce
	 *
	 * @return true
	 * @throws RequirementsHelperException
	 */
	public static function check_dependencies( string $platformVersion, string $cmsVersion ): bool {
		if ( ! defined( 'WC_VERSION' ) ) {
			throw new RequirementsHelperException(
				__( 'Alma requires WooCommerce to be activated' )
			);
		}

		// Check WordPress version
		if ( version_compare( $platformVersion, self::MIN_WORDPRESS_VERSION, '<' ) ) {
			throw new RequirementsHelperException(
				sprintf(
				// translators: %s is the minimum WordPress version required to run the plugin.
					__( 'Alma requires WordPress version %s or greater' ),
					self::MIN_WORDPRESS_VERSION
				)
			);
		}

		if ( version_compare( $cmsVersion, self::MIN_WOOCOMMERCE_VERSION, '<' ) ) {
			throw new RequirementsHelperException(
				sprintf(
				// translators: %s is the minimum WooCommerce version required to run the plugin.
					__( 'Alma requires WooCommerce version %s or greater' ),
					self::MIN_WOOCOMMERCE_VERSION
				)
			);
		}

		return true;
	}
}
