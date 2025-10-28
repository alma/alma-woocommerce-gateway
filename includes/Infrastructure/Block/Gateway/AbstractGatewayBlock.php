<?php
/**
 * AlmaBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks
 * @namespace Alma\Woocommerce\Blocks;
 */

namespace Alma\Gateway\Infrastructure\Block\Gateway;

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Block\CheckoutBlockException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\Frontend\AbstractFrontendGateway;
use Alma\Gateway\Infrastructure\Helper\BlockHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks
 */
abstract class AbstractGatewayBlock extends AbstractPaymentMethodType {

	protected AbstractFrontendGateway $gateway;
	private ConfigService $config_service;
	private AssetsService $assets_service;

	public function __construct( ConfigService $config_service, AssetsService $assets_service ) {
		$this->config_service = $config_service;
		$this->assets_service = $assets_service;

		$this->initialize();
	}

	/**
	 * Returns the gateway associated with this block.
	 *
	 * @return AbstractFrontendGateway
	 */
	public function get_gateway(): AbstractFrontendGateway {
		return $this->gateway;
	}

	/**
	 * Returns true if this payment method should be active with blocks.
	 * Return false if:
	 * - not admin or checkout page
	 * - gateway is not enabled or not available
	 * - blocks are not enabled in settings or woocommerce checkout blocks are not used
	 * If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 * @throws FeePlanRepositoryException
	 */
	public function is_active(): bool {
		return true;
		// Check page
		if ( ! ContextHelper::isAdmin() && ! ContextHelper::isCheckoutPage() ) {
			return false;
		}

		// Check gateway
		if ( ! $this->gateway->is_enabled() || ! $this->gateway->is_available() ) {
			return false;
		}

		// Check blocks
		$pageId = get_the_ID() ? get_the_ID() : 0;
		if ( ! $this->config_service->isBlocksEnabled() || ! BlockHelper::has_woocommerce_checkout_blocks( $pageId ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register the scripts to be loaded for the payment method.
	 * The handle returned here is used by WooCommerce to enqueue the script.
	 * Params are passed to the script.
	 *
	 * @return string[]
	 * @throws CheckoutBlockException
	 *
	 * @todo move to GatewayService::initGatewayBlocks this could be done only once.
	 */
	public function get_payment_method_script_handles(): array {

		$params['checkout_url'] = ContextHelper::getWebhookUrl( 'alma_checkout_data' );

		try {
			$this->assets_service->loadCheckoutBlockAssets( $params );
		} catch ( AssetsServiceException $e ) {
			throw new CheckoutBlockException( 'Unable to load block assets', 0, $e );
		}

		return array( 'alma-block-integration' );
	}

	/**
	 * Send data to the js. Used by default by WooCommerce for static gateway data.
	 * To use it in the payment method script, use script like:
	 * const settings = window.wc.wcSettings.getSetting(`alma_paynow_gateway_block_data`, null)
	 * @return array
	 * @see src/alma-gateway-block/alma-gateway-block.js
	 */
	public function get_payment_method_data(): array {

		return array(
			'name'         => $this->get_name(),
			'title'        => $this->gateway->get_title(),
			'description'  => $this->gateway->get_description(),
			'gateway_name' => $this->gateway->get_name(),
			'label_button' => L10nHelper::__( 'Pay With Alma', 'alma-gateway-for-woocommerce' ),
		);
	}
}
