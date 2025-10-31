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
use Alma\Gateway\Infrastructure\Gateway\Frontend\AbstractFrontendGateway;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Alma\Gateway\Infrastructure\Service\CheckoutService;
use Alma\Gateway\Plugin;
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
		$this->name           = 'alma_' . $this->gateway->get_name() . '_gateway_block';
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
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active(): bool {
		return true;

		// return $this->config_service->isBlocksEnabled() && $this->gateway->is_enabled() && $this->gateway->is_available();
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

		/** @var CheckoutService $checkoutService */
		$checkoutService = Plugin::get_container()->get( CheckoutService::class );
		$params          = $checkoutService->getCheckoutParams();

		$params['checkout_url'] = ContextHelper::getWebhookUrl( 'alma_checkout_data' );

		try {
			$this->assets_service->loadCheckoutBlockAssets( $params );
		} catch ( AssetsServiceException $e ) {
			throw new CheckoutBlockException( 'Unable to load block assets', 0, $e );
		}

		return array( 'alma-block-integration' );
	}
}
