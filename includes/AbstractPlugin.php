<?php

namespace Alma\Gateway;

use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;
use Alma\Gateway\Application\Helper\RequirementsHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\PluginException;
use Alma\Gateway\Infrastructure\Helper\AdminNotificationHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AbstractPlugin {

	/** @var bool $failsafe_mode */
	protected static $failsafe_mode = false;

	/** @var bool The plugin prerequisites status. */
	private static bool $plugin_prerequisites = false;

	/** @var string The plugin url. */
	private static string $plugin_url;

	/** @var string The plugin path. */
	private static string $plugin_path;

	/** @var string The plugin filename. */
	private static string $plugin_file;

	/** @var bool Whether the plugin is configured or not. */
	private bool $is_configured = false;

	/** @var bool Whether the plugin is enabled or not. */
	private bool $is_enabled = false;

	/**
	 * Return true if the plugin prerequisites are ok.
	 * @return bool True if the plugin prerequisites are ok.
	 */
	public static function are_prerequisites_ok(): bool {
		return self::$plugin_prerequisites;
	}

	/**
	 * Return true if the plugin is in failsafe mode.
	 *
	 * @return bool
	 */
	public static function is_failsafe_mode(): bool {
		return self::$failsafe_mode;
	}

	/**
	 * Enable the failsafe mode and notify the admin.
	 *
	 * @param string $message The message to display in the notification.
	 */
	protected static function enable_failsafe_mode( string $message ): void {
		self::$failsafe_mode = true;
		AdminNotificationHelper::notifyError( $message );
	}

	/**
	 * Check if the plugin can load. Is woocommerce installed? It's mandatory.
	 * We don't use Dice because it's not loaded yet.
	 *
	 * @return bool
	 * @throws PluginException
	 */
	public function check_prerequisites(): bool {
		// Check if WooCommerce is active
		if ( ! ContextHelper::isCmsLoaded() ) {
			self::$plugin_prerequisites = false;

			return false;
		}

		// Check if all dependencies are met
		try {
			if ( ! RequirementsHelper::check_dependencies( ContextHelper::getCmsVersion() ) ) {
				self::$plugin_prerequisites = false;

				return false;
			}
		} catch ( RequirementsHelperException $e ) {
			throw new PluginException( 'Plugin requirements are not met', 0, $e );
		}

		self::$plugin_prerequisites = true;

		return true;
	}

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
	 *
	 * @param bool $force_refresh If true, re-evaluate the configuration state.
	 *
	 * @return bool
	 */
	public function is_configured( bool $force_refresh = false ): bool {
		if ( $force_refresh ) {
			// Re-evaluate the configuration state
			/** @var ConfigService $config_service */
			$config_service = Plugin::get_container()->get( ConfigService::class );
			$this->set_is_configured( $config_service->isConfigured() );
		}

		return $this->is_configured;
	}

	/**
	 * Set true if the Plugin is enabled
	 *
	 * @param bool $is_enabled
	 *
	 * @return void
	 */
	public function set_is_enabled( bool $is_enabled ) {
		$this->is_enabled = $is_enabled;
	}

	/**
	 * Return the enabled state of the Plugin
	 *
	 * @param bool $force_refresh If true, re-evaluate the configuration state.
	 *
	 * @return bool
	 */
	public function is_enabled( bool $force_refresh = false ): bool {
		if ( $force_refresh ) {
			// Re-evaluate the configuration state
			/** @var ConfigService $config_service */
			$config_service = Plugin::get_container()->get( ConfigService::class );
			$this->set_is_enabled( $config_service->isEnabled() );
		}

		return $this->is_enabled;
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
