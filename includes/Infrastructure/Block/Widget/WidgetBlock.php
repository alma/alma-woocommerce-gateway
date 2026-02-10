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

namespace Alma\Gateway\Infrastructure\Block\Widget;

use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Block\WidgetBlockException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Mapper\FeePlanListMapper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks
 */
class WidgetBlock implements IntegrationInterface {

	/** @var ConfigService The settings. */
	protected ConfigService $config_service;

	/** @var ContextHelper */
	private ContextHelper $context_helper;

	/** @var FeePlanRepository */
	private FeePlanRepository $fee_plan_repository;

	/** @var GatewayRepository The Gateway Repository */
	private GatewayRepository $gateway_repository;

	/** @var ExcludedProductsHelper */
	private ExcludedProductsHelper $excluded_products_helper;

	public function __construct(
		ConfigService $config_service,
		FeePlanRepository $fee_plan_repository,
		GatewayRepository $gateway_repository,
		ContextHelper $context_helper,
		ExcludedProductsHelper $excluded_products_helper
	) {
		$this->config_service           = $config_service;
		$this->fee_plan_repository      = $fee_plan_repository;
		$this->gateway_repository       = $gateway_repository;
		$this->context_helper           = $context_helper;
		$this->excluded_products_helper = $excluded_products_helper;
	}

	public function get_name(): string {
		return 'alma-widget-block';
	}

	public function initialize() {
	}

	public function get_script_handles(): array {
		return array( 'alma-widget-block' );
	}

	public function get_editor_script_handles(): array {
		return array( 'alma-widget-block' );
	}

	/**
	 * Send data to the js.
	 * Create new scratch file from selection
	 * const settings = window.wc.wcSettings.getSetting(`alma-widget-block_data`, null);
	 * @return array
	 * @throws WidgetBlockException
	 * @see src/alma-widget-block/AlmaWidget.js
	 */
	public function get_script_data(): array {

		$excludedCategories = $this->config_service->getExcludedCategories();

		$cart_adapter = ContextHelper::getCart();

		$canDisplayWidgetOnCart = $this->excluded_products_helper->canDisplayOnCartPage(
			$cart_adapter,
			$excludedCategories
		);
		try {
			$feePlanList = $this->fee_plan_repository->getAllWithEligibility()->filterEnabled()->orderBy( $this->gateway_repository->findOrderedAlmaGateways() );
		} catch ( FeePlanRepositoryException $e ) {
			throw new WidgetBlockException( 'Can not send data to JS', 0, $e );
		}

		return array(
			'merchant_id'                 => $this->config_service->getMerchantId(),
			'environment'                 => strtoupper( $this->config_service->getEnvironment()->getMode() ),
			'plans'                       => ( new FeePlanListMapper() )->buildFeePlanListDto( $feePlanList )->toArray()['plans'],
			'amount'                      => $cart_adapter->getCartTotal(),
			'locale'                      => $this->context_helper->getLanguage(),
			'can_be_displayed'            => count( $feePlanList ) > 0 && $this->config_service->getWidgetCartEnabled(),
			'is_excluded_categories'      => ! $canDisplayWidgetOnCart,
			'excluded_categories_message' => $this->config_service->getExcludedCategoriesMessage(),
			'url_alma_logo'               => esc_url( AssetsHelper::getImage( 'images/alma_logo.svg' ) ),
		);
	}
}
