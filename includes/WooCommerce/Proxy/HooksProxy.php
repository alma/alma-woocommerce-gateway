<?php

namespace Alma\Gateway\WooCommerce\Proxy;

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
	public static function load_language( $domain, $plugin_path ) {
		self::add_action(
			'init',
			function () use ( $domain, $plugin_path ) {
				load_plugin_textdomain(
					$domain,
					false,
					$plugin_path . '/languages'
				);
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
	private static function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		add_action( $hook_name, $callback, $priority, $accepted_args );
	}

	public static function load_gateway( $gateway_class_name ) {
		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) use ( $gateway_class_name ) {
				array_unshift( $gateways, $gateway_class_name );

				return $gateways;
			}
		);
	}

	public static function add_gateway_links( $base_path, $callback ) {
		add_filter(
			'plugin_action_links_' . plugin_basename( $base_path ),
			$callback
		);
	}
}
