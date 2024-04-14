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

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockHelper
 */
class BlockHelper {


    public function __construct() {
        $this->logger           = new AlmaLogger();
        $this->settings_helper      = new AlmaSettings();
    }


    /**
	 * Is woocommerce block activated ?
	 *
	 * @return bool
	 */
	public function has_woocommerce_blocks() {
        $this->logger->info('$this->settings_helper->is_blocks_enabled() ');
        $this->logger->info($this->settings_helper->is_blocks_enabled() ? 'true' : 'false');

        // Check if the required class exists.
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) || ! wp_is_block_theme() || !$this->settings_helper->is_blocks_enabled() ) {
			return false;
		}

		return true;
	}
}

