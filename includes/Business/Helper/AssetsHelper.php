<?php

namespace Alma\Gateway\Business\Helper;

class AssetsHelper {

	/**
	 * @var mixed
	 */
	private $plugin_url;

	/**
	 * AssetsHelper constructor.
	 * Define plugin url for assets.
	 *
	 * @param $plugin_url
	 */
	public function __construct( $plugin_url ) {
		$this->plugin_url = $plugin_url;
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
		return $this->plugin_url . 'assets/' . $path;
	}
}
