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
use Alma\Woocommerce\Exceptions\Alma_Requirements;
use Alma\Woocommerce\Helpers\Alma_Constants;
use Alma\Woocommerce\Helpers\Alma_Tools;
use Alma\Woocommerce\Helpers\Alma_Payment;
use Alma\Woocommerce\Helpers\Alma_Assets;

/**
 * Alma_Plugin.
 */
class Alma_Plugin {


	/**
	 * The *Singleton* instance of this class
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
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		$this->logger = new Alma_Logger();
		$this->self_update();
		$this->init();
	}

	/**
	 * Update plugin to the latest version.
	 *
	 * @return void
	 */
	protected function self_update() {
		$db_version = get_option( 'alma_version' );

		if ( version_compare( ALMA_VERSION, $db_version, '!=' ) ) {

			if (
				$db_version
				&& version_compare( $db_version, 4, '<' )
				&& version_compare( ALMA_VERSION, 4, '>=' )
			) {
				$old_settings = get_option( 'woocommerce_alma_settings' );
				update_option( Alma_Settings::OPTIONS_KEY, $old_settings );

				// Upgrade to 4.
				$gateway = new Alma_Payment_Gateway();

				// Manage credentials to match the new settings fields format
				try {
					$gateway->manage_credentials();
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// We don't care if it fails there is nothing to update.
				}

				if ( version_compare( $db_version, 3, '<' ) ) {
					update_option( 'alma_version', ALMA_VERSION );
					deactivate_plugins( 'alma-woocommerce-gateway/alma-woocommerce-gateway.php', true );
				}
			}

			update_option( 'alma_version', ALMA_VERSION );
		}
	}

	/**
	 * Init the plugin after plugins_loaded so environment variables are set.
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 */
	public function init() {
		$this->admin_notices = new Alma_Notices();

		try {
			$this->check_dependencies();
		} catch ( \Exception $e ) {
			$this->admin_notices->add_admin_notice( 'alma_global_error', 'notice notice-error', $e->getMessage(), true );
			$this->logger->error( $e->getMessage() );
			return;
		}

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ALMA_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );

		$this->load_plugin_textdomain();

		$this->add_hooks();
		$this->add_badges();
		$this->add_actions();
		$this->init_soc();

	}

	/**
	 * Check dependencies.
	 *
	 * @return void
	 * @throws Alma_Requirements   Alma_Requirements.
	 */
	protected function check_dependencies() {

		if ( ! function_exists( 'WC' ) ) {
			throw new Alma_Requirements( __( 'Alma requires WooCommerce to be activated', 'alma-gateway-for-woocommerce' ) );
		}

		if ( version_compare( wc()->version, '2.6', '<' ) ) {
			throw new Alma_Requirements( __( 'Alma requires WooCommerce version 2.6 or greater', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new Alma_Requirements( __( 'Alma requires the cURL PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new Alma_Requirements( __( 'Alma requires the JSON PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		$openssl_warning = __( 'Alma requires OpenSSL >= 1.0.1 to be installed on your server', 'alma-gateway-for-woocommerce' );
		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new Alma_Requirements( $openssl_warning );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new Alma_Requirements( $openssl_warning );
		}

		if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
			throw new Alma_Requirements( $openssl_warning );
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
	 * It’s important to note that adding hooks inside gateway classes may not trigger.
	 * Gateways are only loaded when needed, such as during checkout and on the settings page in admin.
	 *
	 * @return void
	 */
	protected function add_hooks() {
		add_action(
			Alma_Tools::action_for_webhook( Alma_Constants::CUSTOMER_RETURN ),
			array(
				$this,
				'handle_customer_return',
			)
		);

		add_action(
			Alma_Tools::action_for_webhook( Alma_Constants::IPN_CALLBACK ),
			array(
				$this,
				'handle_ipn_callback',
			)
		);
	}

	/**
	 * Add the badges.
	 *
	 * @return void
	 */
	protected function add_badges() {

			$settings = new Alma_Settings();

		if (
				$settings->is_enabled()
				&& $settings->is_allowed_to_see_alma( wp_get_current_user() )
			) {

			$shortcodes = new Alma_Shortcodes();

			$cart_handler = new Alma_Cart_Handler();
			$shortcodes->init_cart_widget_shortcode( $cart_handler );

			$product_handler = new Alma_Product_Handler();
			$shortcodes->init_product_widget_shortcode( $product_handler );

			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		}

	}

	/**
	 * Add the wp actions.
	 *
	 * @return void
	 */
	protected function add_actions() {
		$payment_upon_trigger_helper = new Alma_Payment_Upon_Trigger();
		add_action(
			'woocommerce_order_status_changed',
			array(
				$payment_upon_trigger_helper,
				'woocommerce_order_status_changed',
			),
			10,
			3
		);

		$refund = new Alma_Refund();
		add_action( 'admin_init', array( $refund, 'admin_init' ) );

		// add_action( 'init', array( $this, 'check_share_checkout' ) );
	}

	/**
	 * Init the share of checkout.
	 *
	 * @return void
	 */
	protected function init_soc() {
		/**
		 * // Launch the "share of checkout".
		 * $share_of_checkout = new Alma_Share_Of_Checkout();
		 * $share_of_checkout->init();
		 */
	}

	/**
	 * Returns the *Singleton* instance of this class.
	 * ngleton* instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Handle ipn callback.
	 *
	 * @return void
	 */
	public function handle_ipn_callback() {
		$payment_helper = new Alma_Payment();
		$payment_helper->handle_ipn_callback();
	}


	/**
	 * Handle customer return.
	 *
	 * @return void
	 */
	public function handle_customer_return() {
		$payment_helper = new Alma_Payment();
		$order          = $payment_helper->handle_customer_return();

		// Redirect user to the order confirmation page.
		$alma_gateway = new Alma_Payment_Gateway();

		$return_url = $alma_gateway->get_return_url( $order->get_order() );
		wp_safe_redirect( $return_url );
		exit();
	}

	/**
	 * Inject JS in checkout page.
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		if ( is_checkout() ) {
			$alma_checkout = Alma_Assets::get_asset_url( Alma_Constants::ALMA_PATH_CHECKOUT_JS );
			wp_enqueue_script( 'alma-checkout-page', $alma_checkout, array(), ALMA_VERSION, true );
		}
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
		$gateways[] = Alma_Payment_Gateway::class;

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
		$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;

		$section_slug = $use_id_as_section ? 'alma' : strtolower( Alma_Payment_Gateway::class );

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}

	/**
	 * Private clone method to prevent cloning of the instance of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {    }

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __wakeup() {   }
}
