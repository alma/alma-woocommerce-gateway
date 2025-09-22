<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class PluginHelper {

	/** @var string The plugin url. */
	private static string $pluginUrl;

	/** @var string The plugin path. */
	private static string $pluginPath;

	/** @var string The plugin filename. */
	private static string $pluginFile;


	/**
	 * Define if we can load the plugin.
	 * True on cart or checkout page if the plugin is configured for frontend use.
	 *
	 * @throws ContainerServiceException
	 */
	public static function isPluginNeeded(): bool {

		// Are we on the cart page?
		// If everything is ok, we can load the plugin
		if ( self::isConfigured() && ContextHelper::isCartProductOrCheckoutPage() ) {
			return true;
		}

		return false;
	}

	/**
	 * @throws ContainerServiceException
	 */
	public static function isConfigured(): bool {
		/** @var ConfigService $configService */
		$configService = Plugin::get_container()->get( ConfigService::class );

		return $configService->isConfigured();
	}

	/**
	 * Return the plugin version.
	 *
	 * @return string
	 */
	public static function getPluginVersion(): string {
		return Plugin::ALMA_GATEWAY_PLUGIN_VERSION;
	}

	/**
	 * Get plugin url.
	 *
	 * @return string
	 */
	public static function getPluginUrl(): string {
		return self::$pluginUrl;
	}

	/**
	 * Set plugin url.
	 *
	 * @param string $plugin_url
	 */
	public static function setPluginUrl( string $plugin_url ) {
		self::$pluginUrl = $plugin_url;
	}

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public static function getPluginPath(): string {
		return self::$pluginPath;
	}

	/**
	 * Set plugin path.
	 *
	 * @param string $pluginPath
	 */
	public static function setPluginPath( string $pluginPath ) {
		self::$pluginPath = $pluginPath;
	}

	/**
	 * Return the plugin filename.
	 *
	 * @return string
	 */
	public static function getPluginFile(): string {
		return self::$pluginFile;
	}

	/**
	 * Set plugin filename.
	 *
	 * @param string $pluginFile
	 */
	public static function setPluginFile( string $pluginFile ) {
		self::$pluginFile = $pluginFile;
	}
}
