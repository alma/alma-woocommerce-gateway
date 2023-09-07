<?php
/**
 * Alma_Plugin.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Admin\Alma_Notices;
use Alma\Woocommerce\Exceptions\Alma_Requirements_Exception;
use Alma\Woocommerce\Exceptions\Alma_Version_Deprecated;
use Alma\Woocommerce\Gateways\Inpage\Alma_Payment_Gateway_In_Page;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Pay_Later;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Pay_More_Than_Four;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Pay_Now;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Standard;
use Alma\Woocommerce\Helpers\Alma_Migration_Helper;
use Alma\Woocommerce\Helpers\Alma_Plugin_Helper;

/**
 * Alma_Plugin.
 */
class Alma_Plugin {


	/**
	 * The *Singleton* instance of this class.
	 *
	 * @var Alma_Plugin The instance.
	 */
	private static $instance;
	/**
	 * Admin notices.
	 *
	 * @var Alma_Notices
	 */
	public $admin_notices;
	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;

	/**
	 * The migration helper.
	 *
	 * @var Alma_Migration_Helper
	 */
	protected $migration_helper;

	/**
	 * The plugin helper
	 *
	 * @var Alma_Plugin_Helper
	 */
	protected $plugin_helper;

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		$this->logger = new Alma_Logger();

		$this->migration_helper = new Alma_Migration_Helper();
		$this->admin_notices    = new Alma_Notices();
		$this->plugin_helper    = new Alma_Plugin_Helper();

		$this->load_plugin_textdomain();
		try {
			$migration_success = $this->migration_helper->update();
		} catch ( Alma_Version_Deprecated $e ) {
			$this->admin_notices->add_admin_notice( 'alma_version_error', 'notice notice-error', $e->getMessage(), true );
			$this->logger->error( $e->getMessage() );
			return;
		}

		if ( $migration_success ) {
			$this->init();
		} else {
			$this->logger->warning( 'The plugin migration is already in progress or has failed' );
		}
	}

	/**
	 * Init the plugin after plugins_loaded so environment variables are set.
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 */
	public function init() {
		try {
			$this->check_dependencies();
		} catch ( \Exception $e ) {
			$this->admin_notices->add_admin_notice( 'alma_global_error', 'notice notice-error', $e->getMessage(), true );
			$this->logger->error( $e->getMessage() );
			return;
		}

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ALMA_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );

		$this->plugin_helper->add_hooks();
		$this->plugin_helper->add_shortcodes_and_scripts();
		$this->plugin_helper->add_actions();
	}

	/**
	 * Check dependencies.
	 *
	 * @return void
	 * @throws Alma_Requirements_Exception   Alma_Requirements_Exception.
	 */
	protected function check_dependencies() {

		if ( ! function_exists( 'WC' ) ) {
			throw new Alma_Requirements_Exception( __( 'Alma requires WooCommerce to be activated', 'alma-gateway-for-woocommerce' ) );
		}

		if ( version_compare( wc()->version, '3.0.0', '<' ) ) {
			throw new Alma_Requirements_Exception( __( 'Alma requires WooCommerce version 3.0.0 or greater', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new Alma_Requirements_Exception( __( 'Alma requires the cURL PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new Alma_Requirements_Exception( __( 'Alma requires the JSON PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		$openssl_warning = __( 'Alma requires OpenSSL >= 1.0.1 to be installed on your server', 'alma-gateway-for-woocommerce' );
		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new Alma_Requirements_Exception( $openssl_warning );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new Alma_Requirements_Exception( $openssl_warning );
		}

		if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
			throw new Alma_Requirements_Exception( $openssl_warning );
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'alma-gateway-for-woocommerce', false, plugin_basename( ALMA_PLUGIN_PATH ) . '/languages' );
	}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Alma_Plugin
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add the gateway to WC Available Gateways.
	 *
	 * @param array $gateways all available WC gateways.
	 *
	 * @return array $gateways all WC gateways + offline gateway.
	 * @since 1.0.0
	 */
	public function add_gateways( $gateways ) {
		if ( ! is_admin() ) {
			$gateways[] = \Alma\Woocommerce\Gateways\Inpage\Alma_Payment_Gateway_Pay_Now::class;
			$gateways[] = Alma_Payment_Gateway_Pay_Now::class;
		}

		$gateways[] = Alma_Payment_Gateway_Standard::class;

		if ( ! is_admin() ) {
			$gateways[] = Alma_Payment_Gateway_In_Page::class;
			$gateways[] = Alma_Payment_Gateway_Pay_More_Than_Four::class;
			$gateways[] = Alma_Payment_Gateway_Pay_Later::class;
		}

		return $gateways;
	}

	/**
	 * Adds plugin action links
	 *
	 * @param array $links Plugin action link before filtering.
	 *
	 * @return array Filtered links.
	 */
	public function plugin_action_links( $links ) {
		$setting_link = $this->get_setting_link();

		$plugin_links = array(
			'<a href="' . $setting_link . '">' . __( 'Settings', 'alma-gateway-for-woocommerce' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Get setting link.
	 *
	 * @return string Setting link
	 * @since 1.0.0
	 */
	public function get_setting_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=alma' );
	}

	/**
	 * Private clone method to prevent cloning of the instance of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {    }

	/**
	 * Public unserialize method to prevent unserializing of the *Singleton* instance.
	 *
	 * @return void
	 */
	public function __wakeup() {   }
}
