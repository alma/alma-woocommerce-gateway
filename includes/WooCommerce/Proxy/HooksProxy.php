<?php

namespace Alma\Gateway\WooCommerce\Proxy;

use Alma\Gateway\WooCommerce\Model\Gateway;

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

	public static function load_gateway() {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				array_unshift( $gateways, Gateway::class );

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
