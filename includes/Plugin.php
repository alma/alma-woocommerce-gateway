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

use Alma\Gateway\Business\Exception\CoreException;
use Alma\Gateway\Business\Exceptions\RequirementsException;
use Alma\Gateway\Business\Service\AdminService;
use Alma\Gateway\Business\Service\ContainerService;
use Alma\Gateway\Business\Service\GatewayService;
use Alma\Gateway\Business\Service\WooCommerceService;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * AlmaPlugin.
 */
final class Plugin {

	const ALMA_GATEWAY_PLUGIN_VERSION = '6.0.0-poc';

	const ALMA_GATEWAY_PLUGIN_NAME = 'alma-gateway-for-woocommerce';

	const WOOCOMMERCE_PLUGIN_REFERENCE = 'woocommerce/woocommerce.php';

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
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	private $plugin_url = '';
	/**
	 * Path to this plugin's directory.
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
	 */
	public function plugin_setup() {
		if ( ! $this->can_i_load() ) {
			return;
		}
		$this->plugin_url  = plugins_url( '/', __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );

		// more stuff: register actions and filters
		HooksProxy::load_language( self::ALMA_GATEWAY_PLUGIN_NAME, $this->get_plugin_path() );
		if ( is_admin() ) {
			$admin_service = self::get_container()->get( AdminService::class );
		}

		if ( ! is_admin() ) {
			$gateway_service = self::get_container()->get( GatewayService::class );
			$gateway_service->load_gateway();
		}
	}

	/**
	 * Check if the plugin can load. Is woocommerce installed? It's mandatory.
	 *
	 * @return bool
	 * @throws RequirementsException
	 */
	public function can_i_load() {
		// Check dependencies
		if ( ! $this->check_dependencies() ) {
			return false;
		}
		// Check if WooCommerce is active
		if ( ! in_array( self::WOOCOMMERCE_PLUGIN_REFERENCE, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			return false;
		}

		return true;
	}

	/**
	 * Check if we met dependencies.
	 *
	 * @return true
	 * @throws RequirementsException
	 */
	private function check_dependencies() {
		if ( ! function_exists( 'WC' ) ) {
			throw new RequirementsException( __( 'Alma requires WooCommerce to be activated', 'alma-gateway-for-woocommerce' ) );
		}

		if ( version_compare( WooCommerceService::get_version(), '3.0.0', '<' ) ) {
			throw new RequirementsException( __( 'Alma requires WooCommerce version 3.0.0 or greater', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new RequirementsException( __( 'Alma requires the cURL PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new RequirementsException( __( 'Alma requires the JSON PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		$openssl_warning = __( 'Alma requires OpenSSL >= 1.0.1 to be installed on your server', 'alma-gateway-for-woocommerce' );
		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new RequirementsException( $openssl_warning );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new RequirementsException( $openssl_warning );
		}

		return true;
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
	 * Return the DI container
	 * @return ContainerService|null
	 */
	public static function get_container() {
		return self::$container;
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
		return __FILE__;
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
