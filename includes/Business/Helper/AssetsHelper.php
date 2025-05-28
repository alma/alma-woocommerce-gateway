<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AssetsHelper {

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
