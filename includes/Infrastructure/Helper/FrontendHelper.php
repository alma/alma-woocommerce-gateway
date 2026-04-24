<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Backend\AlmaGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Plugin;

class FrontendHelper {

	/**
	 * Alma frontend gateway IDs in desired display order.
	 *
	 * @var string[]
	 */
	private static array $alma_gateway_ids = [];

	/**
	 * The backend config gateway ID used by WooCommerce admin ordering.
	 *
	 * @var string
	 */
	private static string $config_gateway_id = '';

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
	 */
	public static function loadFrontendGateways( array $almaGatewayList ) {
		self::initGatewayIds();

		add_filter( 'woocommerce_payment_gateways', [ self::class, 'registerGateways' ] );
		add_filter( 'option_woocommerce_gateway_order', [ self::class, 'syncGatewayOrder' ] );
		add_filter( 'woocommerce_available_payment_gateways', [ self::class, 'sortAlmaGateways' ] );

		// Store the list for the registration callback.
		self::$almaGatewayList = $almaGatewayList;
	}

	/**
	 * Initialize the gateway ID arrays once.
	 *
	 * @return void
	 */
	private static function initGatewayIds(): void {
		if ( ! empty( self::$alma_gateway_ids ) ) {
			return;
		}

		self::$alma_gateway_ids = [
			sprintf( AbstractGateway::NAME_ALMA_GATEWAYS, PayNowGateway::PAYMENT_METHOD ),
			sprintf( AbstractGateway::NAME_ALMA_GATEWAYS, PnxGateway::PAYMENT_METHOD ),
			sprintf( AbstractGateway::NAME_ALMA_GATEWAYS, PayLaterGateway::PAYMENT_METHOD ),
			sprintf( AbstractGateway::NAME_ALMA_GATEWAYS, CreditGateway::PAYMENT_METHOD ),
		];

		self::$config_gateway_id = sprintf( AbstractGateway::NAME_ALMA_GATEWAYS, AlmaGateway::PAYMENT_METHOD );
	}

	/**
	 * @var array Gateway class list passed from loadFrontendGateways.
	 */
	private static array $almaGatewayList = [];

	/**
	 * Register enabled Alma gateways in WooCommerce.
	 *
	 * @param array $gateways
	 *
	 * @return array
	 */
	public static function registerGateways( array $gateways ): array {
		$container = Plugin::get_container();

		/** @var AbstractGateway $gateway */
		foreach ( self::$almaGatewayList as $gatewayClass ) {
			if ( ! in_array( $gatewayClass, $gateways, true ) && class_exists( $gatewayClass ) ) {
				$gateway = $container->get( $gatewayClass );
				if ( $gateway->is_enabled() ) {
					$gateways[] = $gateway;
				}
			}
		}

		return $gateways;
	}

	/**
	 * Inject Alma frontend gateway IDs into WooCommerce's gateway ordering
	 * so that sort_gateways() positions them at the same place as alma_config_gateway.
	 * Without this, frontend IDs are unknown to WooCommerce and get order 999+.
	 *
	 * @param mixed $ordering
	 *
	 * @return array
	 */
	public static function syncGatewayOrder( $ordering ): array {
		if ( ! is_array( $ordering ) ) {
			$ordering = [];
		}

		// Remove any existing Alma frontend gateway entries to avoid duplicates.
		foreach ( self::$alma_gateway_ids as $id ) {
			unset( $ordering[ $id ] );
		}

		$base_order = isset( $ordering[ self::$config_gateway_id ] )
			? absint( $ordering[ self::$config_gateway_id ] )
			: 0;

		$alma_count = count( self::$alma_gateway_ids );

		// Shift all non-Alma gateways with order >= base_order to make room.
		foreach ( $ordering as $id => $order ) {
			if ( $id !== self::$config_gateway_id && absint( $order ) >= $base_order ) {
				$ordering[ $id ] = absint( $order ) + $alma_count;
			}
		}

		// Insert Alma frontend gateways at the base position.
		$ordering += array_combine(
			self::$alma_gateway_ids,
			range( $base_order, $base_order + $alma_count - 1 )
		);

		return $ordering;
	}

	/**
	 * Ensure correct relative order among Alma gateways.
	 * After sort_gateways(), all Alma gateways share the same order value,
	 * but their relative order is not guaranteed (uasort is unstable in PHP < 8.0).
	 * This filter groups them in the desired order at their current position.
	 *
	 * @param $gateways
	 *
	 * @return mixed|array
	 */
	public static function sortAlmaGateways( $gateways ) {
		if ( ! is_array( $gateways ) ) {
			return $gateways;
		}

		$alma_gateways = [];
		foreach ( self::$alma_gateway_ids as $id ) {
			if ( isset( $gateways[ $id ] ) ) {
				$alma_gateways[ $id ] = $gateways[ $id ];
			}
		}

		if ( empty( $alma_gateways ) ) {
			return $gateways;
		}

		$result        = [];
		$alma_inserted = false;

		foreach ( $gateways as $id => $gateway ) {
			if ( isset( $alma_gateways[ $id ] ) ) {
				if ( ! $alma_inserted ) {
					$result       += $alma_gateways;
					$alma_inserted = true;
				}
				continue;
			}
			$result[ $id ] = $gateway;
		}

		return $result;
	}
}
