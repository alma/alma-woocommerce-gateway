<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Gateway\Backend\AlmaGateway;

class BackendHelper {

	/**
	 * Run services on admin init.
	 *
	 * @param callable $callback
	 */
	public static function runBackendServices( callable $callback ) {
		add_action( 'admin_init', $callback );
	}

	/**
	 * Load the Alma payment gateway in WooCommerce.
	 *
	 * @return void
	 */
	public function loadBackendGateway() {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				array_unshift( $gateways, AlmaGateway::class );

				return $gateways;
			}
		);
	}
}
