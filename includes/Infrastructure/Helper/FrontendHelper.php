<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
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
	 * Display the services on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 *
	 * @param callable $callback Function to run on template_redirect.
	 */
	public static function displayFrontendServices( callable $callback ) {
		EventHelper::addEvent( 'template_redirect', $callback, 20 );
	}

	/**
	 * Load the frontend gateways.
	 *
	 * @param array $almaGatewayList
	 *
	 * @return void
	 *
	 * @sonar Easier to understand with two if statements.
	 */
	public static function loadFrontendGateways( array $almaGatewayList ) {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) use ( $almaGatewayList ) {
				$container = Plugin::get_container();

				/** @var AbstractGateway $gateway */
				foreach ( $almaGatewayList as $gatewayClass ) {
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

		// Sort gateways order (not Blocks).
		add_filter( 'woocommerce_available_payment_gateways', function ( $gateways ) {
			$order = [
				Plugin::get_container()->get( PayNowGateway::class )->get_name(),   // first
				Plugin::get_container()->get( PayNowGateway::class )->get_name() . '_block',   // first
				Plugin::get_container()->get( PnxGateway::class )->get_name(),      // second
				Plugin::get_container()->get( PnxGateway::class )->get_name() . '_block',      // second
				Plugin::get_container()->get( PayLaterGateway::class )->get_name(), // third
				Plugin::get_container()->get( PayLaterGateway::class )->get_name() . '_block', // third
				Plugin::get_container()->get( CreditGateway::class )->get_name(),   // fourth
				Plugin::get_container()->get( CreditGateway::class )->get_name() . '_block',   // fourth
			];

			$sorted = [];
			foreach ( $order as $id ) {
				if ( isset( $gateways[ $id ] ) ) {
					$sorted[ $id ] = $gateways[ $id ];
				}
			}

			// We keep other gateways that could be registered by other plugins at the end of the list
			foreach ( $gateways as $id => $gateway ) {
				if ( ! isset( $sorted[ $id ] ) ) {
					$sorted[ $id ] = $gateway;
				}
			}

			return $sorted;
		} );
	}
}
