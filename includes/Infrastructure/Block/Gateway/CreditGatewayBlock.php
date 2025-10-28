<?php
/**
 * PayMoreThanFourBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks/Standard
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Gateway\Infrastructure\Block\Gateway;

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * PayMoreThanFourBlock
 */
final class CreditGatewayBlock extends AbstractGatewayBlock implements IntegrationInterface {

	public function __construct( ConfigService $config_service, AssetsService $assets_service ) {
		$this->name    = 'alma_credit_gateway_block';
		$this->gateway = new CreditGateway();
		parent::__construct( $config_service, $assets_service );
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 * @todo implements https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration/#processing-payments-via-the-store-api
	 */
	public function initialize() {
	}
}
