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
	 * Conditional function that check if Cart page use Cart Blocks
	 */
	public function has_woocommerce_cart_blocks() {
		return \WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'cart' ), 'woocommerce/cart' );
	}

	/**
	 * Conditional function that check if Checkout page use Checkout Blocks
	 */
	public function has_woocommerce_checkout_blocks() {
		return \WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' );
	}
}
