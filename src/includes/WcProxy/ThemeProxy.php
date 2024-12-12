<?php
/**
 * Theme proxy.
 *
 * @package Alma\Woocommerce\WcProxy
 */

namespace Alma\Woocommerce\WcProxy;

use WP_Theme;

/**
 * Theme proxy.
 */
class ThemeProxy {

	/**
	 * Get theme data.
	 *
	 * @return WP_Theme
	 */
	private function get_theme() {
		return wp_get_theme();
	}

	/**
	 * Get theme name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->get_theme()->get( 'Name' );
	}

	/**
	 * Get theme version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->get_theme()->get( 'Version' );
	}

}
