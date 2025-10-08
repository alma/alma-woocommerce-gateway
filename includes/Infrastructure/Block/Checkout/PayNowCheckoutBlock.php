<?php
/**
 * PayNowBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks/Standard
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Gateway\Infrastructure\Block\Checkout;

use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks_Pay_Now.
 */
final class PayNowCheckoutBlock extends AbstractCheckoutBlock implements IntegrationInterface {

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {

		$this->name    = 'alma_checkout_paynow_block';
		$this->gateway = new PayNowGateway();

		parent::initialize();
	}
}
