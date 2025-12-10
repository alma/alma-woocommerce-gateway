<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Alma\Gateway\Plugin;

class FrontendHelper {

	/**
	 * Run services on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 *
	 * @param callable $callback Function to run on template_redirect.
	 */
	public static function runFrontendServices( callable $callback ) {
		EventHelper::addEvent( 'template_redirect', $callback );
	}

	/**
	 * Load the frontend gateways.
	 *
	 * @return void
	 * @todo Define the order of the gateways to be loaded.
	 * @sonar Easier to understand with two if statements.
	 */
	public static function loadFrontendGateways() {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				$container = Plugin::get_container();

				$alma_gateway_list = array(
					CreditGateway::class,
					PayLaterGateway::class,
					PayNowGateway::class,
					PnxGateway::class,
				);
				/** @var AbstractGateway $gateway */
				foreach ( $alma_gateway_list as $gatewayClass ) {
					if ( ! in_array( $gatewayClass, $gateways, true ) && class_exists( $gatewayClass ) ) {
						// Check if the gateway is enabled before adding it to the list.
						$gateway = $container->get( $gatewayClass );
						if ( $gateway->is_enabled() ) { // NOSONAR -- Easier to understand with two if statements.
							array_unshift( $gateways, $gateway );
						}
					}
				}

				return $gateways;
			}
		);
	}
}
