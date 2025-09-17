<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Application\Helper\PluginHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AssetsHelper {

	/**
	 * Enqueue a script.
	 *
	 * @param string $version String specifying the script version number, if it has one.
	 *
	 * @return void
	 */
	public static function enqueueAdminScript( string $version ): void {
		wp_enqueue_script(
			'alma-admin-script',
			self::getAssetUrl( 'js/backend/alma-admin.js' ),
			[ 'jquery' ],
			$version,
			true );
	}

	/**
	 * Localize a script.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public static function configureAdminScript( array $data ): void {
		wp_localize_script( 'alma-admin-script', 'alma_settings', $data );
	}

	/**
	 * Enqueue the Alma widget style.
	 *
	 * @return void
	 */
	public static function enqueueWidgetStyle() {
		wp_enqueue_style(
			'alma-frontend-widget-cdn',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css'
		);
	}

	/**
	 * Enqueue the Alma widget scripts.
	 *
	 * @param string $version String specifying the script version number, if it has one.
	 *
	 * @return void
	 */
	public static function enqueueWidgetScript( string $version ): void {
		wp_enqueue_script(
			'alma-frontend-widget-cdn',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js'
		);

		wp_enqueue_script(
			'alma-frontend-widget-implementation',
			self::getAssetUrl( 'js/frontend/alma-frontend-widget-implementation.js' ),
			[ 'jquery' ],
			$version,
			true
		);

	}

	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function getAssetUrl( string $path ): string {
		$pluginUrl = PluginHelper::getPluginUrl();

		return $pluginUrl . 'assets/' . $path;
	}

	/**
	 * Get the url for the image to display
	 *
	 * @param string $path By default, the alma logo.
	 *
	 * @return string
	 */
	public function getImage( string $path = 'images/alma_logo.svg' ): string {
		return $this->getAssetUrl( $path );
	}
}
