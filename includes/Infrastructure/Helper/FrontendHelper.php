<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;

class FrontendHelper {

	/**
	 * Run services on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 *
	 * @param callable $callback
	 */
	public static function runFrontendServices( callable $callback ) {
		add_action( 'template_redirect', $callback );
	}

	/**
	 * Load the frontend gateways.
	 *
	 * @return void
	 * @todo Define the order of the gateways to be loaded.
	 * @sonar Easier to understand with two if statements.
	 */
	public function loadFrontendGateways() {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				$alma_gateway_list = array(
					CreditGateway::class,
					PayLaterGateway::class,
					PayNowGateway::class,
					PnxGateway::class,
				);
				/** @var AbstractGateway $gateway */
				foreach ( $alma_gateway_list as $gateway ) {
					if ( ! in_array( $gateway, $gateways, true ) && class_exists( $gateway ) ) {
						// Check if the gateway is enabled before adding it to the list.
						if ( ( new $gateway() )->is_enabled() ) { // NOSONAR -- Easier to understand with two if statements.
							array_unshift( $gateways, $gateway );
						}
					}
				}

				return $gateways;
			}
		);
	}
}
