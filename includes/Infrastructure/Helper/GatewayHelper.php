<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Class GatewayHelper.
 */
class GatewayHelper {

	/**
	 * Add links to the plugin on the plugins page.
	 *
	 * @param string   $base_path Path to the main plugin file.
	 * @param callable $callback Callback to add the links.
	 *
	 * @return void
	 */
	public static function addGatewayLinks( string $base_path, callable $callback ) {
		add_filter(
			'plugin_action_links_' . plugin_basename( $base_path ),
			$callback
		);
	}
}
