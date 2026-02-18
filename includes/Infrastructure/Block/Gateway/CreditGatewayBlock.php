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

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * PayMoreThanFourBlock
 */
final class CreditGatewayBlock extends AbstractGatewayBlock implements IntegrationInterface {

	public function __construct( bool $is_in_page_enabled, string $assets_handle ) {
		$this->gateway = new CreditGateway();
		parent::__construct( $is_in_page_enabled, $assets_handle );
	}
}
