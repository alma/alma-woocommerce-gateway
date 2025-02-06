<?php
/**
 * Option proxy.
 *
 * @package Alma\Woocommerce\WcProxy
 */

namespace Alma\Woocommerce\WcProxy;

/**
 * Class OptionProxy
 */
class OptionProxy {


	/**
	 * Get option.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get_option( $option_name, $default = false ) {
		return get_option( $option_name, $default );
	}

}
