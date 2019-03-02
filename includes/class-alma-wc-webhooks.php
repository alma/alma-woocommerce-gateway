<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 */
class Alma_WC_Webhooks {
	const CustomerReturn = 'alma_customer_return';
	const IpnCallback = 'alma_ipn_callback';

	public static function url_for( $webhook ) {
		return wc()->api_request_url( $webhook );
	}

	public static function action_for( $webhook ) {
		return "woocommerce_api_$webhook";
	}
}
