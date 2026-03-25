<?php

namespace Alma\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\OrderStatusService;
use Alma\Gateway\Infrastructure\Controller\AdminController;
use Alma\Gateway\Infrastructure\Controller\GatewayController;
use Alma\Gateway\Infrastructure\Controller\ShopController;
use Alma\Gateway\Infrastructure\Exception\PluginException;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Gateway\Infrastructure\Service\MigrationService;
use Exception;

/**
 * AlmaPlugin.
 */
final class Plugin extends AbstractPlugin {

	const ALMA_GATEWAY_PLUGIN_VERSION = '6.0.6';

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
	 */
	public function plugin_warmup(): void {

		try {
			if ( ! $this->check_prerequisites() || self::is_failsafe_mode() ) {
				return;
			}

			// Configure Languages
			L10nHelper::load_language( self::ALMA_GATEWAY_PLUGIN_NAME );

			// Check mandatory prerequisites
			if ( ! self::are_prerequisites_ok() ) {
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

			// Configure the logger service
			/** @var LoggerService $logger_service */
			self::get_container()->get( LoggerService::class, $suffix );

			/** @var ConfigService $config_service */
			$config_service = self::get_container()->get( ConfigService::class );
			$this->set_is_configured( $config_service->isConfigured() );

			/** @var BusinessEventsRepository $business_event */
			$business_event = self::get_container()->get( BusinessEventsRepository::class );
			$business_event->createTableIfNotExists();
		} catch ( Exception $e ) {
			self::get_container()->get( LoggerService::class )->debug(
				'Warmup failed with error: ' . $e->getMessage(),
				[ 'exception' => $e ]
			);
			self::enable_failsafe_mode( __( 'The Alma plugin does not appear to be compatible with your version of WooCommerce. Please contact support for more information.' ) );
		}
	}

	/**
	 * Used for plugin migration.
	 *
	 * @return void
	 */
	public function plugin_migration(): void {

		try {
			if ( ! self::are_prerequisites_ok() || self::is_failsafe_mode() ) {
				return;
			}

			/** @var MigrationService $migration_service */
			$migration_service = self::get_container()->get( MigrationService::class );
			if ( $migration_service->runMigrationsIfNeeded() ) {
				$this->set_is_configured( true );
			}
		} catch ( Exception $e ) {
			self::get_container()->get( LoggerService::class )->debug(
				'Migration failed with error: ' . $e->getMessage(),
				[ 'exception' => $e ]
			);
			self::enable_failsafe_mode( __( 'The Alma plugin does not appear to be compatible with your version of WooCommerce. Please contact support for more information.' ) );
		}
	}

	/**
	 * Used for regular plugin work.
	 *
	 */
	public function plugin_setup(): void {

		try {
			if ( ! self::are_prerequisites_ok() || self::is_failsafe_mode() ) {
				return;
			}

			// Run Admin Controller only when WordPress admin is ready.
			/** @var AdminController $adminController */
			$adminController = self::get_container()->get( AdminController::class );

			/** @var GatewayController $gatewayController */
			$gatewayController = self::get_container()->get( GatewayController::class );

			/** @var ShopController $shopController */
			$shopController = self::get_container()->get( ShopController::class );

			// Run Admin Controller only when WordPress admin is ready.
			$adminController->prepare();
			$adminController->display();

			if ( $this->is_configured() ) {

				/** @var OrderStatusService $orderStatusService */
				$orderStatusService = self::get_container()->get( OrderStatusService::class );
				$orderStatusService->initSendOrderStatusHook();

				$this->get_container()->setApiConfig();
				$gatewayController->prepare();

				// Plugin fully configured, let's run the services
				$gatewayController->run();

				if ( $this->is_enabled( true ) ) {

					// Register widgets
					$shopController->prepare();

					// Run services only when WordPress frontend is ready.
					$shopController->run();

					// Display services only when WordPress frontend is ready.
					$shopController->display();
				}

			} else {

				// Plugin not yet configured, load only backend gateway to help in configuration.
				$gatewayController->configure();
			}
		} catch ( Exception $e ) {
			self::get_container()->get( LoggerService::class )->debug(
				'Setup failed with error: ' . $e->getMessage(),
				[ 'exception' => $e ]
			);
			self::enable_failsafe_mode( __( 'The Alma plugin does not appear to be compatible with your version of WooCommerce. Please contact support for more information.' ) );
		}
	}

	/**
	 * Singletons should not be restored from strings.
	 *
	 * @return void
	 * @throws PluginException if called
	 */
	public function __wakeup() {
		throw new PluginException( 'Cannot serialize or unserialize a plugin!' );
	}

	/**
	 * Clone is not allowed.
	 *
	 * @return void
	 * @throws PluginException
	 */
	private function __clone() {
		throw new PluginException( 'Cannot clone the plugin!' );
	}
}
