<?php
/**
 * PayLaterBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks/Standard
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Gateway\Infrastructure\Block\Gateway;

use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * PayLaterBlock
 */
final class PayLaterGatewayBlock extends AbstractGatewayBlock implements IntegrationInterface {

	public function __construct( bool $is_in_page_enabled, string $assets_handle ) {
		$this->gateway = new PayLaterGateway();
		parent::__construct( $is_in_page_enabled, $assets_handle );
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 * @todo implements https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration/#processing-payments-via-the-store-api
	 */
	public function initialize() {
	}
}
