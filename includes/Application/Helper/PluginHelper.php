<?php

namespace Alma\Gateway\Application\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class PluginHelper {

	/**
	 * The plugin url.
	 *
	 * @var string
	 */
	private static string $plugin_url;

	/**
	 * Get plugin url.
	 *
	 * @return string
	 */
	public function get_plugin_url(): string {
		return self::$plugin_url;
	}

	/**
	 * Set plugin url.
	 *
	 * @param string $plugin_url
	 */
	public function set_plugin_url( string $plugin_url ) {
		self::$plugin_url = $plugin_url;
	}
}
