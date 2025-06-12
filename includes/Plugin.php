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

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\RequirementsException;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Helper\PluginHelper;
use Alma\Gateway\Business\Helper\RequirementsHelper;
use Alma\Gateway\Business\Service\AdminService;
use Alma\Gateway\Business\Service\API\EligibilityService;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\Business\Service\ContainerService;
use Alma\Gateway\Business\Service\GatewayService;
use Alma\Gateway\Business\Service\LoggerService;
use Alma\Gateway\Business\Service\OptionsService;
use Alma\Gateway\WooCommerce\Exception\CoreException;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

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
	 * @see get_instance()
	 * @type Null|Plugin
	 */
	private static ?Plugin $instance = null;

	/** @var Null|ContainerService The DI Container. */
	private static ?ContainerService $container = null;

	/** @type string $plugin_url URL to the root plugin's directory. */
	private string $plugin_url = '';

	/** @type string $plugin_path Path to the root plugin's directory. */
	private string $plugin_path = '';

	/** @var PluginHelper $plugin_helper */
	private PluginHelper $plugin_helper;

	/** @var LoggerService $logger_service */
	private LoggerService $logger_service;

	/**
	 * Constructor.
	 *
	 * @see plugin_setup()
	 */
	public function __construct() {
	}

	/**
	 * Return the DI container
	 * @return ContainerService|null
	 */
	public static function get_container(): ContainerService {
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
	 * Used for regular plugin work.
	 *
	 * @return  void
	 * @throws ContainerException
	 * @throws RequirementsException
	 */
	public function plugin_setup() {
		self::$container = new ContainerService();

		/** @var PluginHelper $plugin_helper */
		$plugin_helper       = self::get_container()->get( PluginHelper::class );
		$this->plugin_helper = $plugin_helper;
		/** @var LoggerService $logger_service */
		$logger_service       = self::get_container()->get( LoggerService::class );
		$this->logger_service = $logger_service;

		if ( ! $this->are_prerequisites_ok() ) {
			return;
		}

		$this->plugin_url = plugins_url( '/', __DIR__ );
		$this->plugin_helper->set_plugin_url( $this->plugin_url );
		$this->plugin_path = plugin_dir_path( __DIR__ );

		// Configure Helpers
		L10nHelper::load_language( $this->get_plugin_path() );

		// Init Services
		if ( is_admin() ) {
			self::get_container()->get( AdminService::class );
		}

		/** @var GatewayService $gateway_service */
		$gateway_service = self::get_container()->get( GatewayService::class );
		if ( $this->is_configured() ) {
			/** @var EligibilityService $eligibility_service */
			$eligibility_service = self::get_container()->get( EligibilityService::class );
			$gateway_service->set_eligibility_service( $eligibility_service );
			/** @var FeePlanService $fee_plan_service */
			$fee_plan_service = self::get_container()->get( FeePlanService::class );
			$gateway_service->set_fee_plan_service( $fee_plan_service );
		}

		$gateway_service->load_gateway();

		// Run services only when WordPress admin is ready.
		HooksProxy::run_backend_services(
			function () {
				// Init Backend Services
			}
		);

		// Run services only when WordPress frontend is ready.
		HooksProxy::run_frontend_services(
			function () {
				if ( ! $this->is_plugin_needed() ) {
					return;
				}

				/** @var GatewayService $gateway_service */
				$gateway_service = self::get_container()->get( GatewayService::class );
				$gateway_service->configure_gateway();
			}
		);
	}

	/**
	 * Check if the plugin can load. Is woocommerce installed? It's mandatory.
	 *
	 * @return bool
	 * @throws RequirementsException
	 * @throws ContainerException
	 */
	public function are_prerequisites_ok(): bool {
		// Check if all dependencies are met
		/** @var RequirementsHelper $requirements_helper */
		$requirements_helper = self::get_container()->get( RequirementsHelper::class );
		if ( ! $requirements_helper->check_dependencies() ) {
			return false;
		}
		// Check if WooCommerce is active
		WooCommerceProxy::is_woocommerce_loaded();

		return true;
	}

	/**
	 * @throws ContainerException
	 */
	public function is_configured(): bool {
		$options_service = self::get_container()->get( OptionsService::class );

		if ( $options_service->is_configured() ) {
			$this->logger_service->debug( 'Plugin is configured' );
		} else {
			$this->logger_service->debug( 'Plugin is NOT configured' );
		}

		return $options_service->is_configured();
	}

	/**
	 * Define if we can load the plugin.
	 * True on cart or checkout page if the plugin is configured for frontend use.
	 * @throws ContainerException
	 */
	public function is_plugin_needed(): bool {

		// Are we on the cart page?
		// If everything is ok, we can load the plugin
		if ( $this->is_configured() && WooCommerceProxy::is_cart_or_checkout_page() ) {
			return true;
		}

		return false;
	}

	/**
	 * Return the plugin path.
	 *
	 * @return string
	 */
	public function get_plugin_path(): string {
		return $this->plugin_path;
	}

	/**
	 * Return the plugin url.
	 *
	 * @return string
	 */
	public function get_plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Return the plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return self::ALMA_GATEWAY_PLUGIN_VERSION;
	}

	/**
	 * Return the plugin filename.
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return dirname( __DIR__ ) . '/alma-gateway-for-woocommerce.php';
	}

	/**
	 * Singletons should not be restored from strings.
	 *
	 * @return void
	 * @throws CoreException if called
	 */
	public function __wakeup() {
		throw new CoreException( 'Cannot serialize or unserialize a plugin!' );
	}

	/**
	 * Clone is not allowed.
	 *
	 * @return void
	 */
	private function __clone() {
	}
}
