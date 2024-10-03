<?php
/**
 * AlmaPlugin.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Admin\Services\NoticesService;
use Alma\Woocommerce\Exceptions\RequirementsException;
use Alma\Woocommerce\Exceptions\VersionDeprecated;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Gateways\Inpage\InPageGateway;
use Alma\Woocommerce\Gateways\Inpage\PayLaterGateway as InPagePayLaterGateway;
use Alma\Woocommerce\Gateways\Inpage\PayMoreThanFourGateway as InPagePayMoreThanFourGateway;
use Alma\Woocommerce\Gateways\Inpage\PayNowGateway as InPagePayNowGateway;
use Alma\Woocommerce\Gateways\Standard\PayLaterGateway;
use Alma\Woocommerce\Gateways\Standard\PayMoreThanFourGateway;
use Alma\Woocommerce\Gateways\Standard\PayNowGateway;
use Alma\Woocommerce\Gateways\Standard\StandardGateway;
use Alma\Woocommerce\Helpers\MigrationHelper;
use Alma\Woocommerce\Helpers\PluginHelper;

/**
 * AlmaPlugin.
 */
class AlmaPlugin {


	/**
	 * The *Singleton* instance of this class.
	 *
	 * @var AlmaPlugin The instance.
	 */
	private static $instance;
	/**
	 * Admin notices.
	 *
	 * @var NoticesService
	 */
	public $admin_notices;
	/**
	 * The logger.
	 *
	 * @var AlmaLogger
	 */
	protected $logger;

	/**
	 * The migration helper.
	 *
	 * @var MigrationHelper
	 */
	protected $migration_helper;

	/**
	 * The plugin helper
	 *
	 * @var PluginHelper
	 */
	protected $plugin_helper;

	/**
	 * The version factory.
	 *
	 * @var VersionFactory
	 */
	protected $version_factory;

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		$this->logger           = new AlmaLogger();
		$this->migration_helper = new MigrationHelper();
		$this->admin_notices    = new NoticesService();
		$this->plugin_helper    = new PluginHelper();
		$this->version_factory  = new VersionFactory();

		$this->load_plugin_textdomain();

		try {
			$migration_success = $this->migration_helper->update();
		} catch ( VersionDeprecated $e ) {
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
	 * @throws RequirementsException   RequirementsException.
	 */
	protected function check_dependencies() {

		if ( ! function_exists( 'WC' ) ) {
			throw new RequirementsException( __( 'Alma requires WooCommerce to be activated', 'alma-gateway-for-woocommerce' ) );
		}

		if ( version_compare( $this->version_factory->get_version(), '3.0.0', '<' ) ) {
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

		if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
			throw new RequirementsException( $openssl_warning );
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
	 * @return AlmaPlugin
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
			$gateways[] = InPagePayNowGateway::class;
			$gateways[] = PayNowGateway::class;
			$gateways[] = InPageGateway::class;
			$gateways[] = PayMoreThanFourGateway::class;
			$gateways[] = InPagePayLaterGateway::class;
			$gateways[] = InPagePayMoreThanFourGateway::class;
			$gateways[] = PayLaterGateway::class;
		}

		$gateways[] = StandardGateway::class;
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
