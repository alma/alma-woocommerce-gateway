<?php

namespace Alma\Gateway\Business\Helper;

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
	public function get_admin_logs_url() {
		return admin_url( 'admin.php?page=wc-status&tab=logs' );
	}

	/**
	 * Get the url for the image to display
	 *
	 * @param string $path By default, the alma logo.
	 *
	 * @return string
	 */
	public function get_image( $path = 'images/alma_logo.svg' ) {
		return $this->get_asset_url( $path );
	}

	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public function get_asset_url( $path ) {

		/** @var PluginHelper $plugin_helper */
		$plugin_url = Plugin::get_instance()->get_container()->get( PluginHelper::class )->get_plugin_url();

		return $plugin_url . 'assets/' . $path;
	}
}
