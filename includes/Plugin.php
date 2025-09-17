<?php
/**
 * FormHtmlBuilder.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/Gateway/Business
 * @namespace Alma\Gateway\Business
 */

namespace Alma\Gateway;

use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\API\Infrastructure\Exception\PluginException;
use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;
use Alma\Gateway\Application\Exception\Service\ShopServiceException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Helper\RequirementsHelper;
use Alma\Gateway\Application\Service\AdminService;
use Alma\Gateway\Application\Service\API\EligibilityService;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Application\Service\GatewayService;
use Alma\Gateway\Application\Service\ShopService;
use Alma\Gateway\Infrastructure\Exception\CmsException;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Alma\Gateway\Infrastructure\Service\LoggerService;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * AlmaPlugin.
 */
final class Plugin {

	const ALMA_GATEWAY_PLUGIN_VERSION = '6.0.0-poc';

	const ALMA_GATEWAY_PLUGIN_NAME = 'alma-gateway-for-woocommerce';

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type Null|Plugin
	 */
	private static ?Plugin $instance = null;

	/** @var Null|ContainerService The DI Container. */
	private static ?ContainerService $container = null;

	/** @var LoggerService $logger_service */
	private LoggerService $logger_service;

	/** @var ContextHelperInterface $contextHelper Gives information about context */
	private ContextHelperInterface $contextHelper;

	/**
	 * Constructor.
	 *
	 * @see plugin_setup()
	 */
	public function __construct() {
		// Configure the plugin paths
		PluginHelper::setPluginUrl( plugins_url( '/', __DIR__ ) );
		PluginHelper::setPluginPath( plugin_dir_path( __DIR__ ) );
		PluginHelper::setPluginFile( dirname( __DIR__ ) . '/alma-gateway-for-woocommerce.php' );
	}

	/**
	 * Return the DI container
	 *
	 * @param bool $force_refresh If true, the container will be recreated.
	 *
	 * @return ContainerService|null
	 */
	public static function get_container( bool $force_refresh = false ): ContainerService {
		if ( $force_refresh || null === self::$container ) {
			self::$container = new ContainerService();
		}

		/** @var ContainerService $container */
		return self::$container;
	}

	/**
	 * Access this plugin’s working instance
	 *
	 * @return  object of this class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Used for plugin warmup.
	 *
	 * @throws ContainerServiceException
	 * @throws RequirementsHelperException
	 */
	public function plugin_warmup(): void {

		// Configure Languages
		L10nHelper::load_language( PluginHelper::getPluginPath() );

		// Check mandatory prerequisites
		if ( ! $this->are_prerequisites_ok() ) {
			return;
		}

		// Set the DI container
		self::get_container( true );

		/** @var ContextHelper $contextHelper */
		$contextHelper       = self::get_container()->get( ContextHelper::class );
		$this->contextHelper = $contextHelper;

		// Set the plugin helper and logger service
		/** @var LoggerService $logger_service */
		$logger_service       = self::get_container()->get( LoggerService::class );
		$this->logger_service = $logger_service;

		// Configure the gateways
		/** @var GatewayService $gateway_service */
		$gateway_service = self::get_container()->get( GatewayService::class );
		if ( PluginHelper::isConfigured() ) {
			/** @var EligibilityService $eligibility_service */
			$eligibility_service = self::get_container()->get( EligibilityService::class );
			$gateway_service->setEligibilityService( $eligibility_service );
			/** @var FeePlanService $fee_plan_service */
			$fee_plan_service = self::get_container()->get( FeePlanService::class );
			$gateway_service->setFeePlanService( $fee_plan_service );
		}
	}

	/**
	 * Used for regular plugin work.
	 *
	 * @return  void
	 * @throws ContainerServiceException
	 * @throws RequirementsHelperException
	 * @throws PluginException
	 */
	public function plugin_setup(): void {

		if ( ! $this->are_prerequisites_ok() ) {
			return;
		}

		/** @var GatewayService $gateway_service */
		$gateway_service = self::get_container()->get( GatewayService::class );
		$gateway_service->loadGateway();
		if ( PluginHelper::isConfigured() ) {
			$gateway_service->configureReturns();
		}

		// Run services only when WordPress admin is ready.
		/** @var AdminService $adminService */
		$adminService = self::get_container()->get( AdminService::class );
		$adminService->runService();

		// Run services only when WordPress frontend is ready.
		try {
			/** @var ShopService $shopService */
			$shopService = self::get_container()->get( ShopService::class );
			$shopService->runService();
		} catch ( ShopServiceException $e ) {
			throw new PluginException( $e->getMessage() );
		}
	}

	/**
	 * Check if the plugin can load. Is woocommerce installed? It's mandatory.
	 * We don't use Dice because it's not loaded yet.
	 *
	 * @return bool
	 * @throws RequirementsHelperException
	 */
	public function are_prerequisites_ok(): bool {
		// Check if WooCommerce is active
		if ( ! ContextHelper::isCmsLoaded() ) {
			return false;
		}

		// Check if all dependencies are met
		$requirements_helper = new RequirementsHelper();
		if ( ! $requirements_helper->check_dependencies( ContextHelper::getCmsVersion() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Singletons should not be restored from strings.
	 *
	 * @return void
	 * @throws CmsException if called
	 */
	public function __wakeup() {
		throw new CmsException( 'Cannot serialize or unserialize a plugin!' );
	}

	/**
	 * Clone is not allowed.
	 *
	 * @return void
	 * @throws CmsException
	 */
	private function __clone() {
		throw new CmsException( 'Cannot clone the plugin!' );
	}
}
