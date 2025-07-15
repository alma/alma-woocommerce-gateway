<?php

namespace Alma\Gateway\WooCommerce\Proxy;

use Alma\Gateway\Business\Gateway\Backend\AlmaGateway;
use Alma\Gateway\Business\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Business\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;

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

	/**
	 * Load the frontend gateways.
	 * @return void
	 * @todo Define the order of the gateways to be loaded.
	 * @sonar Easier to understand with two if statements.
	 */
	public static function load_frontend_gateways() {
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
		self::add_action( 'admin_init', $callback );
	}

	/**
	 * Run services on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 *
	 * @param callable $callback
	 */
	public static function run_frontend_services( callable $callback ) {
		self::add_action( 'template_redirect', $callback );
	}


	/**
	 * Reload the DI container when the plugin options are updated.
	 * This is useful to ensure that the latest options are used in the application.
	 *
	 * @return void
	 */
	public static function auto_reload_di_container_on_option_save() {
		self::add_action(
			'woocommerce_update_options_payment_gateways_alma_config_gateway',
			function () {
				Plugin::get_container( true );
			}
		);
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
