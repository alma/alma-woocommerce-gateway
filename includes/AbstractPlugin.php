<?php

namespace Alma\Gateway;

use Alma\Gateway\Infrastructure\Helper\ContextHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AbstractPlugin {

	/** @var string The plugin url. */
	private static string $plugin_url;
	/** @var string The plugin path. */
	private static string $plugin_path;
	/** @var string The plugin filename. */
	private static string $plugin_file;

	private bool $is_configured = false;

	/**
	 * Define if we can load the plugin.
	 * True on cart or checkout page if the plugin is configured for frontend use.
	 */
	public function is_plugin_needed(): bool {

		// Are we on the cart page?
		// If everything is ok, we can load the plugin
		if ( self::is_configured() && ContextHelper::isShop() ) {
			return true;
		}

		return false;
	}

	/**
	 * Set true if the Plugin is configured
	 *
	 * @param bool $is_configured
	 *
	 * @return void
	 */
	public function set_is_configured( bool $is_configured ) {
		$this->is_configured = $is_configured;
	}

	/**
	 * Return the config state of the Plugin
	 * @return bool
	 */
	public function is_configured(): bool {
		return $this->is_configured;
	}

	/**
	 * Return the plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return Plugin::ALMA_GATEWAY_PLUGIN_VERSION;
	}

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

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public function get_plugin_path(): string {
		return self::$plugin_path;
	}

	/**
	 * Set plugin path.
	 *
	 * @param string $pluginPath
	 */
	public function set_plugin_path( string $pluginPath ) {
		self::$plugin_path = $pluginPath;
	}

	/**
	 * Return the plugin filename.
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return self::$plugin_file;
	}

	/**
	 * Set plugin filename.
	 *
	 * @param string $pluginFile
	 */
	public function set_plugin_file( string $pluginFile ) {
		self::$plugin_file = $pluginFile;
	}
}
