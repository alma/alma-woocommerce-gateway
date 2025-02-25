<?php

namespace Alma\Gateway\WooCommerce;

class HookProxy {

	/**
	 * @param string $domain
	 * @param string $plugin_path
	 *
	 * @return void
	 */
	public static function load_language( $domain, $plugin_path ) {
		self::add_action(
			'init',
			function ( $domain, $plugin_path ) {
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
		add_action(
			$hook_name,
			$callback,
			$priority,
			$accepted_args
		);
	}
}
