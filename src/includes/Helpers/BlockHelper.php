<?php
/**
 * BlockHelper.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\Woocommerce\AlmaSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockHelper
 */
class BlockHelper {

	/**
	 * The Alma Settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->alma_settings = new AlmaSettings();
	}

	/**
	 * Is woocommerce block activated ?
	 *
	 * @return bool
	 */
	public function has_woocommerce_blocks() {
		// Check if the required class exists.
		if (
			! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' )
			|| ! $this->alma_settings->is_blocks_template_enabled()
		) {
			return false;
		}

		return true;
	}
}

