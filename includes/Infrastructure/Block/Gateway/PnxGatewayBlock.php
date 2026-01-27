<?php
/**
 * StandardBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Block/Standard
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Gateway\Infrastructure\Block\Gateway;

use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * StandardBlock
 */
final class PnxGatewayBlock extends AbstractGatewayBlock implements IntegrationInterface {

	public function __construct( bool $is_in_page_enabled, string $assets_handle ) {
		$this->gateway = new PnxGateway();
		parent::__construct( $is_in_page_enabled, $assets_handle );
	}
}
