<?php

namespace Alma\Gateway\Business\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class PluginHelper {
	
	/**
	 * The plugin url.
	 *
	 * @var mixed
	 */
	private $plugin_url;

	/**
	 * Get plugin url.
	 *
	 * @return mixed
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Set plugin url.
	 *
	 * @param $plugin_url
	 *
	 * @return mixed
	 */
	public function set_plugin_url( $plugin_url ) {
		return $this->plugin_url = $plugin_url;
	}
}
