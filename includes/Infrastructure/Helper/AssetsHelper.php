<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AssetsHelper {

	/**
	 * Get asset url.
	 *
	 * @param string $resourcePath Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function getAssetUrl( string $resourcePath ): string {
		$pluginUrl = Plugin::get_instance()->get_plugin_url();

		return $pluginUrl . 'assets/' . $resourcePath;
	}

	/**
	 * @param string $resourcePath
	 *
	 * @return string
	 */
	public static function getBuildUrl( string $resourcePath ): string {

		$pluginUrl = Plugin::get_instance()->get_plugin_url();

		return $pluginUrl . 'build/' . $resourcePath;
	}

	/**
	 * Get the path to the languages' directory.
	 *
	 * @return string
	 */
	public static function getLanguagesPath(): string {

		$pluginUrl = Plugin::get_instance()->get_plugin_url();

		return $pluginUrl . 'languages';
	}

	/**
	 * Get the path to the build directory or a resource into it.
	 *
	 * @param string $resourcePath Path relative to the build directory.
	 *
	 * @return string
	 */
	public static function getBuildPath( $resourcePath = '' ): string {

		$pluginPath = Plugin::get_instance()->get_plugin_path();

		return $pluginPath . 'build/' . $resourcePath;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 *
	 * @return string The cache buster value to use for the given file or the plugin version.
	 */
	public static function getFileVersion( string $file ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		return Plugin::get_instance()->get_plugin_version();
	}

	/**
	 * Get the url for the image to display
	 *
	 * @param string $resourcePath By default, the alma logo.
	 *
	 * @return string
	 */
	public static function getImage( string $resourcePath = 'images/alma_logo.svg' ): string {
		return self::getAssetUrl( $resourcePath );
	}
}
