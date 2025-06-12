<?php

namespace Alma\Gateway\WooCommerce\Proxy;

use Alma\Gateway\Business\Gateway\Backend\AlmaGateway;
use Alma\Gateway\Business\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Business\Gateway\Frontend\PnxGateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HooksProxy {

	/**
	 * @param string $domain
	 * @param string $plugin_path
	 *
	 * @return void
	 */
	public static function load_language( string $domain, string $plugin_path ) {
		self::add_action(
			'plugins_loaded',
			function () use ( $domain, $plugin_path ) {
				load_plugin_textdomain(
					$domain,
					false,
					$plugin_path . '/languages'
				);
			}
		);
	}

	public static function load_backend_gateway() {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				array_unshift( $gateways, AlmaGateway::class );

				return $gateways;
			}
		);
	}

	public static function load_frontend_gateways() {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				array_unshift( $gateways, CreditGateway::class );
				array_unshift( $gateways, PayLaterGateway::class );
				array_unshift( $gateways, PayNowGateway::class );
				array_unshift( $gateways, PnxGateway::class );

				return $gateways;
			}
		);
	}

	public static function add_gateway_links( string $base_path, callable $callback ) {
		add_filter(
			'plugin_action_links_' . plugin_basename( $base_path ),
			$callback
		);
	}

	/**
	 * Run services on admin init.
	 *
	 * @param callable $callback
	 */
	public static function run_backend_services( callable $callback ) {
		// This method can be used to run any services that need to be initialized.
		self::add_action( 'admin_init', $callback );
	}

	/**
	 * Run services on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 *
	 * @param callable $callback
	 */
	public static function run_frontend_services( callable $callback ) {
		// This method can be used to run any services that need to be initialized.
		self::add_action( 'template_redirect', $callback );
	}

	/**
	 * @param string   $hook_name
	 * @param callable $callback
	 * @param int      $priority
	 * @param int      $accepted_args
	 *
	 * @return void
	 */
	private static function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ) {
		add_action( $hook_name, $callback, $priority, $accepted_args );
	}
}
