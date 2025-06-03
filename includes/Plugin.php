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
use Alma\Gateway\Business\Exception\CoreException;
use Alma\Gateway\Business\Exception\RequirementsException;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Helper\PluginHelper;
use Alma\Gateway\Business\Helper\RequirementsHelper;
use Alma\Gateway\Business\Service\AdminService;
use Alma\Gateway\Business\Service\API\EligibilityService;
use Alma\Gateway\Business\Service\ContainerService;
use Alma\Gateway\Business\Service\GatewayService;
use Alma\Gateway\Business\Service\OptionsService;
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
	 *
	 * @see get_instance()
	 * @type Null|Plugin
	 */
	private static ?Plugin $instance = null;

	/**
	 * @var Null|ContainerService The DI Container.
	 */
	private static ?ContainerService $container = null;

	/**
	 * URL to the root plugin's directory.
	 *
	 * @type string
	 */
	private $plugin_url = '';
	/**
	 * Path to the root plugin's directory.
	 *
	 * @type string
	 */
	private $plugin_path = '';
	/**
	 * @var PluginHelper
	 */
	private PluginHelper $plugin_helper;

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
		$this->plugin_helper = self::get_container()->get( PluginHelper::class );

		if ( ! $this->can_i_load() ) {
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

		HooksProxy::run_services(
			function () {
				// If I'm in frontend, check if I should load the gateway
				if ( ! is_admin() && ! $this->should_i_load() ) {
					return;
				}

				// Init Services
				/** @var EligibilityService $eligibility_service */
				$eligibility_service = self::get_container()->get( EligibilityService::class );
				$eligibility_service->init();

				/** @var GatewayService $gateway_service */
				$gateway_service = self::get_container()->get( GatewayService::class );
				$gateway_service->load_gateway();
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
	public function can_i_load(): bool {
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
	public function should_i_load(): bool {
		$options_service = self::get_container()->get( OptionsService::class );

		// If mandatory parameters are set, we can load the plugin
		$is_configured = $options_service->is_configured();

		// Are we on the cart page?
		$is_cart_or_checkout_page = WooCommerceProxy::is_cart_or_checkout_page();

		// If everything is ok, we can load the plugin
		if ( $is_configured && $is_cart_or_checkout_page ) {
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
