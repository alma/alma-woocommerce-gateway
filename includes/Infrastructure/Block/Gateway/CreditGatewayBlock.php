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

use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * PayMoreThanFourBlock
 */
final class CreditGatewayBlock extends AbstractGatewayBlock implements IntegrationInterface {

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {

		$this->name    = 'alma_credit_gateway_block';
		$this->gateway = new CreditGateway();

		parent::initialize();
	}
}
