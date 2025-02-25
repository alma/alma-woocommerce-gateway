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

namespace Alma\Gateway\Business;

use Alma\Gateway\Business\Exception\CoreException;
use Alma\Gateway\WooCommerce\HookProxy;

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
	 */
	public function plugin_setup() {
		if ( ! $this->can_i_load() ) {
			return;
		}
		$this->plugin_url  = plugins_url( '/', __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );

		// more stuff: register actions and filters
		HookProxy::load_language( self::ALMA_GATEWAY_PLUGIN_NAME, $this->get_plugin_path() );
	}

	/**
	 * Check if the plugin can load. Is woocommerce installed? It's mandatory.
	 *
	 * @return bool
	 */
	public function can_i_load() {
		if ( ! in_array( self::WOOCOMMERCE_PLUGIN_REFERENCE, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			return false;
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
