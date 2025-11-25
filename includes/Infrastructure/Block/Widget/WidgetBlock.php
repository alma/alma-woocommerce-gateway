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

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Block\WidgetBlockException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Mapper\FeePlanListMapper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Service\AssetsService;
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

	/** @var CartAdapter The Cart. */
	private CartAdapter $cart_adapter;

	/** @var ContextHelper */
	private ContextHelper $context_helper;

	/** @var FeePlanRepository */
	private FeePlanRepository $fee_plan_repository;

	/** @var AssetsService */
	private AssetsService $assets_service;

	public function __construct( ConfigService $config_service, CartAdapter $cart_adapter, FeePlanRepository $fee_plan_repository, ContextHelper $context_helper, AssetsService $assets_service ) {
		$this->config_service      = $config_service;
		$this->cart_adapter        = $cart_adapter;
		$this->fee_plan_repository = $fee_plan_repository;
		$this->context_helper      = $context_helper;
		$this->assets_service      = $assets_service;
	}

	public function get_name(): string {
		return 'alma-widget-block';
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 * @throws WidgetBlockException
	 */
	public function initialize() {
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
	}

	public function get_script_handles(): array {
		return array( 'alma-widget-block-frontend' );
	}

	public function get_editor_script_handles(): array {
		return array( 'alma-widget-block-editor' );
	}

	/**
	 * Send data to the js.
	 * Create new scratch file from selection
	 * const settings = window.wc.wcSettings.getSetting(`alma-widget-block_data`, null);
	 * @return array
	 * @throws FeePlanRepositoryException
	 * @see src/alma-widget-block/AlmaWidget.js
	 */
	public function get_script_data(): array {

		return array(
			'merchant_id'      => $this->config_service->getMerchantId(),
			'environment'      => strtoupper( $this->config_service->getEnvironment()->getMode() ),
			'plans'            => ( new FeePlanListMapper() )->buildFeePlanListDto( $this->fee_plan_repository->getAll()->filterEnabled() )->toArray()['plans'],
			'amount'           => $this->cart_adapter->getCartTotal(),
			'locale'           => $this->context_helper->getLanguage(),
			'can_be_displayed' => true,
		);
	}

	/**
	 * @throws WidgetBlockException
	 */
	private function register_block_frontend_scripts() {
		try {
			$this->assets_service->loadWidgetBlockAssets();
		} catch ( AssetsServiceException $e ) {
			throw new WidgetBlockException( $e->getMessage() );
		}
	}

	/**
	 * @throws WidgetBlockException
	 */
	private function register_block_editor_scripts() {
		try {
			$this->assets_service->loadWidgetBlockEditorAssets();
		} catch ( AssetsServiceException $e ) {
			throw new WidgetBlockException( $e->getMessage() );
		}
	}
}
