<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AssetsHelper {

	/**
	 * Get Alma full URL depends on test or live mode (sandbox or not)
	 *
	 * @param string $env The environment.
	 * @param string $path as path to add after default scheme://host/ infos.
	 *
	 * @return string as full URL
	 */
	public static function get_alma_dashboard_url( string $env = 'test', string $path = '' ): string {
		if ( 'live' === $env ) {
			/* translators: %s -> path to add after dashboard url */
			return esc_url( sprintf( L10nHelper::__( 'https://dashboard.getalma.eu/%s' ), $path ) );
		}

		/* translators: %s -> path to add after sandbox dashboard url */

		return esc_url( sprintf( L10nHelper::__( 'https://dashboard.sandbox.getalma.eu/%s' ), $path ) );
	}

	/**
	 * Get admin logs url.
	 *
	 * @return string
	 */
	public function get_admin_logs_url(): string {
		return admin_url( 'admin.php?page=wc-status&tab=logs' );
	}

	/**
	 * Get the url for the image to display
	 *
	 * @param string $path By default, the alma logo.
	 *
	 * @return string
	 * @throws ContainerException
	 */
	public function get_image( string $path = 'images/alma_logo.svg' ): string {
		return $this->get_asset_url( $path );
	}

	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 * @throws ContainerException
	 */
	public function get_asset_url( string $path ): string {
		/** @var PluginHelper $plugin_helper */
		$plugin_helper = Plugin::get_instance()->get_container()->get( PluginHelper::class );
		$plugin_url    = $plugin_helper->get_plugin_url();

		return $plugin_url . 'assets/' . $path;
	}
}
