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

use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Block\WidgetBlockException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
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
		wp_enqueue_style(
			'alma-widget-block-frontend',
			PluginHelper::getPluginUrl() . '/build/alma-widget-block/alma-widget-block-view.css',
			array(),
			AssetsHelper::getFileVersion( PluginHelper::getPluginUrl() . '/build/alma-widget-block/alma-widget-block-view.css' )
		);

		$params = array();

		try {
			$this->assets_service->loadWidgetBlockAssets( $params );
		} catch ( AssetsServiceException $e ) {
			throw new WidgetBlockException( $e->getMessage() );
		}

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
	 *
	 * @return array
	 * @throws FeePlanRepositoryException
	 */
	public function get_script_data(): array {
		return array(
			'merchant_id'      => $this->config_service->getMerchantId(),
			'environment'      => strtoupper( $this->config_service->getEnvironment() ),
			'plans'            => $this->fee_plan_repository->getAll(),
			'amount'           => $this->cart_adapter->getCartTotal(),
			'locale'           => $this->context_helper->getLanguage(),
			'can_be_displayed' => true,
		);
	}

	/**
	 * @throws WidgetBlockException
	 */
	private function register_block_frontend_scripts() {
		$script_path       = '/build/alma-widget-block/alma-widget-block-view.js';
		$script_url        = PluginHelper::getPluginUrl() . $script_path;
		$script_asset_path = PluginHelper::getPluginPath() . '/build/alma-widget-block/alma-widget-block-view.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path // NOSONAR - build PHP script with no class.
			: array(
				'dependencies' => array(),
				'version'      => $this->getFileVersion( $script_asset_path ),
			);
		try {
			$this->assets_service->loadWidgetBlockAssets();
		} catch ( AssetsServiceException $e ) {
			throw new WidgetBlockException( $e->getMessage() );
		}
		wp_register_script(
			'alma-widget-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * @throws WidgetBlockException
	 */
	private function register_block_editor_scripts() {
		$script_path       = '/build/alma-widget-block/alma-widget-block.js';
		$script_url        = PluginHelper::getPluginUrl() . $script_path;
		$script_asset_path = PluginHelper::getPluginPath() . '/build/alma-widget-block/alma-widget-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path // NOSONAR - build PHP script with no class.
			: array(
				'dependencies' => array(),
				'version'      => $this->getFileVersion( $script_asset_path ),
			);
		try {
			$this->assets_service->loadWidgetBlockAssets();
		} catch ( AssetsServiceException $e ) {
			throw new WidgetBlockException( $e->getMessage() );
		}
		wp_register_script(
			'alma-widget-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}
}
