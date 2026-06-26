<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Pay attention that this NONCE_SALT is used to encrypt keys in OptionsServiceTest.php. Do not change it.
if ( ! defined( 'NONCE_SALT' ) ) {
	define( 'NONCE_SALT', 'youhou! this is super key!' );
}

// The main plugin file is not loaded in tests, so ALMA_VERSION (which
// Plugin::ALMA_GATEWAY_PLUGIN_VERSION derives from) would be undefined. Read it
// from the plugin header to keep a single source of truth (see ECOM-4303).
if ( ! defined( 'ALMA_VERSION' ) ) {
	preg_match(
		'/^\s*\*\s*Version:\s*([0-9.]+)/m',
		(string) file_get_contents( ABSPATH . 'alma-gateway-for-woocommerce.php' ),
		$alma_version_matches
	);
	define( 'ALMA_VERSION', $alma_version_matches[1] );
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Payment_Gateway {}
}

require_once ABSPATH . '/vendor/autoload.php';
