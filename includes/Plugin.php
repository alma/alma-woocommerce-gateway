<?php

namespace Alma\Gateway;

use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Controller\AdminController;
use Alma\Gateway\Infrastructure\Controller\GatewayController;
use Alma\Gateway\Infrastructure\Controller\ShopController;
use Alma\Gateway\Infrastructure\Exception\CmsException;
use Alma\Gateway\Infrastructure\Exception\Controller\GatewayControllerException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Gateway\Infrastructure\Service\MigrationService;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * AlmaPlugin.
 */
final class Plugin extends AbstractPlugin {

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

	/**
	 * Constructor.
	 *
	 * @see plugin_setup()
	 */
	public function __construct() {
		// Configure the plugin paths
		$this->set_plugin_url( plugins_url( '/', __DIR__ ) );
		$this->set_plugin_path( plugin_dir_path( __DIR__ ) );
		$this->set_plugin_file( dirname( __DIR__ ) . DIRECTORY_SEPARATOR . self::ALMA_GATEWAY_PLUGIN_NAME . '.php' );
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
	 * @return Plugin of this class
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Used for plugin warmup.
	 *
	 * @return void
	 * @throws RequirementsHelperException
	 */
	public function plugin_warmup(): void {

		if ( ! $this->check_prerequisites() ) {
			return;
		}

		// Configure Languages
		L10nHelper::load_language( $this->get_plugin_path() );

		// Check mandatory prerequisites
		if ( ! $this->are_prerequisites_ok() ) {
			return;
		}

		// Set the DI container
		self::get_container( true );

		// Set the plugin helper and logger service
		$suffix = [];
		if ( isset( $_GET['rest_route'] ) && $_GET['rest_route'] === '/wc/store/v1/checkout' ) {
			$suffix = [ sprintf( 'alma-%s', 'rest' ) ];
		}
		if ( isset( $_GET['page_id'] ) && $_GET['page_id'] === '6' ) {
			$suffix = [ sprintf( 'alma-%s', 'cart' ) ];
		}
		if ( isset( $_GET['page_id'] ) && $_GET['page_id'] === '7' ) {
			$suffix = [ sprintf( 'alma-%s', 'checkout' ) ];
		}
		if ( isset( $_GET['product'] ) ) {
			$suffix = [ sprintf( 'alma-%s', 'product' ) ];
		}

		// Configure the logger service
		/** @var LoggerService $logger_service */
		self::get_container()->get( LoggerService::class, $suffix );

		/** @var ConfigService $config_service */
		$config_service = self::get_container()->get( ConfigService::class );
		$this->set_is_configured( $config_service->isConfigured() );

		/** @var BusinessEventsRepository  $business_event */
		$business_event = self::get_container()->get( BusinessEventsRepository::class, $suffix );
		$business_event->createTableIfNotExists();
	}

	/**
	 * Used for plugin migration.
	 *
	 * @return void
	 */
	public function plugin_migration(): void {

		if ( ! $this->are_prerequisites_ok() ) {
			return;
		}

		/** @var MigrationService $migration_service */
		$migration_service = self::get_container()->get( MigrationService::class );
		$migration_service->runMigrationsIfNeeded();
	}

	/**
	 * Used for regular plugin work.
	 *
	 * @throws GatewayControllerException
	 */
	public function plugin_setup(): void {

		if ( ! $this->are_prerequisites_ok() ) {
			return;
		}

		if ( $this->is_configured() ) {

			$this->get_container()->setApiConfig();

			/** @var GatewayController $gatewayController */
			$gatewayController = self::get_container()->get( GatewayController::class );
			$gatewayController->prepare();

			// Register widgets
			/** @var ShopController $shopController */
			$shopController = self::get_container()->get( ShopController::class );
			$shopController->prepare();

			// Plugin fully configured, let's run the services
			$gatewayController->run();

			// Run services only when WordPress frontend is ready.
			$shopController->run();

			// Run Admin Controller only when WordPress admin is ready.
			/** @var AdminController $adminController */
			$adminController = self::get_container()->get( AdminController::class );
			$adminController->display();

			// Display services only when WordPress frontend is ready.
			$shopController->display();

		} else {
			// Plugin not yet configured, load only backend gateway to help in configuration.
			/** @var GatewayController $gatewayController */
			$gatewayController = self::get_container()->get( GatewayController::class );
			$gatewayController->configure();
		}
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
