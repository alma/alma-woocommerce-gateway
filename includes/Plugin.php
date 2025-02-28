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
use Alma\Gateway\Business\Helper\RequirementsHelper;
use Alma\Gateway\Business\Service\AdminService;
use Alma\Gateway\Business\Service\ContainerService;
use Alma\Gateway\Business\Service\GatewayService;
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
	 * @type object
	 */
	private static $instance = null;
	/**
	 * @var null The DI Container.
	 */
	private static $container = null;
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
	 * Constructor.
	 *
	 * @see plugin_setup()
	 */
	public function __construct() {
		self::$container = new ContainerService();
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
	 * @throws RequirementsException
	 * @throws ContainerException
	 */
	public function plugin_setup() {
		if ( ! $this->can_i_load() ) {
			return;
		}

		$this->plugin_url  = plugins_url( '/', __DIR__ );
		$this->plugin_path = plugin_dir_path( __DIR__ );

		// Configure Helpers
		L10nHelper::load_language( $this->get_plugin_path() );

		// Init Services
		if ( is_admin() ) {
			self::get_container()->get( AdminService::class );
		}
		/** @var GatewayService $gateway_service */
		$gateway_service = self::get_container()->get( GatewayService::class );
		$gateway_service->load_gateway();
	}

	/**
	 * Check if the plugin can load. Is woocommerce installed? It's mandatory.
	 *
	 * @return bool
	 * @throws RequirementsException
	 * @throws ContainerException
	 */
	public function can_i_load() {
		// Check if all dependencies are met
		if ( ! self::get_container()->get( RequirementsHelper::class )->check_dependencies() ) {
			return false;
		}
		// Check if WooCommerce is active
		WooCommerceProxy::is_woocommerce_loaded();

		return true;
	}

	/**
	 * Return the DI container
	 * @return ContainerService|null
	 */
	public static function get_container() {
		return self::$container;
	}

	/**
	 * Return the plugin path.
	 *
	 * @return string
	 */
	public function get_plugin_path() {
		return $this->plugin_path;
	}

	/**
	 * Return the plugin url.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Return the plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version() {
		return self::ALMA_GATEWAY_PLUGIN_VERSION;
	}

	/**
	 * Return the plugin filename.
	 *
	 * @return string
	 */
	public function get_plugin_file() {
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
