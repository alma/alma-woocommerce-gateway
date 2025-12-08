<?php

namespace Alma\Gateway;

use Alma\Gateway\Application\Exception\Controller\GatewayControllerException;
use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Helper\RequirementsHelper;
use Alma\Gateway\Infrastructure\Controller\AdminController;
use Alma\Gateway\Infrastructure\Controller\GatewayController;
use Alma\Gateway\Infrastructure\Controller\ShopController;
use Alma\Gateway\Infrastructure\Exception\CmsException;
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
	}

	/**
	 * Used for regular plugin work.
	 *
	 * @return  void
	 * @throws RequirementsHelperException|GatewayControllerException
	 */
	public function plugin_setup(): void {

		if ( ! $this->are_prerequisites_ok() ) {
			return;
		}

		if ( PluginHelper::isConfigured() ) {

			$this->get_container()->setApiConfig();

			// Register widgets
			/** @var ShopController $shopController */
			$shopController = self::get_container()->get( ShopController::class );
			$shopController->warm();

			// Plugin fully configured, let's run the services
			/** @var GatewayController $gatewayController */
			$gatewayController = self::get_container()->get( GatewayController::class );
			$gatewayController->run();

			// Run Admin Controller only when WordPress admin is ready.
			/** @var AdminController $adminController */
			$adminController = self::get_container()->get( AdminController::class );
			$adminController->run();

			// Run services only when WordPress frontend is ready.
			$shopController->run();

		} else {
			// Plugin not yet configured, load only backend gateway to help in configuration.
			/** @var GatewayController $gatewayController */
			$gatewayController = self::get_container()->get( GatewayController::class );
			$gatewayController->configure();
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
		if ( ! RequirementsHelper::check_dependencies( ContextHelper::getCmsVersion() ) ) {
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
