<?php
/**
 * BlockHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockHelper
 */
class BlockHelper {


	/**
	 * Is woocommerce block activated ?
	 *
	 * @return bool
	 */
	public function has_woocommerce_blocks() {
		// Check if the required class exists.
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) || ! wp_is_block_theme() ) {
			return false;
		}

		return true;
	}
}

