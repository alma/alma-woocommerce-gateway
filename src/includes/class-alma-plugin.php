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
use Alma\Woocommerce\Admin\Helpers\Alma_Check_Legal_Helper;
use Alma\Woocommerce\Exceptions\Alma_Requirements_Exception;
use Alma\Woocommerce\Exceptions\Alma_Version_Deprecated;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Migration_Helper;
use Alma\Woocommerce\Helpers\Alma_Order_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Helpers\Alma_Payment_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;

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
	 * The migration helper.
	 *
	 * @var Alma_Migration_Helper
	 */
	protected $migration_helper;

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		$this->logger           = new Alma_Logger();
		$this->migration_helper = new Alma_Migration_Helper();
		$this->admin_notices    = new Alma_Notices();

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

		$this->load_plugin_textdomain();

		$this->add_hooks();
		$this->add_badges();
		$this->add_actions();
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
	 * It’s important to note that adding hooks inside gateway classes may not trigger.
	 * Gateways are only loaded when needed, such as during checkout and on the settings page in admin.
	 *
	 * @return void
	 */
	protected function add_hooks() {
		add_action(
			Alma_Tools_Helper::action_for_webhook( Alma_Constants_Helper::CUSTOMER_RETURN ),
			array(
				$this,
				'handle_customer_return',
			)
		);

		add_action(
			Alma_Tools_Helper::action_for_webhook( Alma_Constants_Helper::IPN_CALLBACK ),
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


			if (
				! empty( $settings->settings['display_in_page'] )
				&& $settings->settings['display_in_page'] == 'yes'
			) {
				add_action( 'wp_ajax_alma_do_checkout_in_page', array( $this, 'alma_do_checkout_in_page' ) );
				add_action( 'wp_ajax_nopriv_alma_do_checkout_in_page', array( $this, 'alma_do_checkout_in_page' ) );

				add_action( 'wp_ajax_alma_return_checkout_in_page', array( $this, 'alma_return_checkout_in_page' ) );
				add_action( 'wp_ajax_nopriv_alma_return_checkout_in_page', array( $this, 'alma_return_checkout_in_page' ) );
			}
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

		$check_legal = new Alma_Check_Legal_Helper();
		add_action( 'init', array( $check_legal, 'check_share_checkout' ) );

		// Launch the "share of checkout".
		$share_of_checkout = new Alma_Share_Of_Checkout();
		add_action( 'init', array( $share_of_checkout, 'send_soc_data' ) );
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
	 * Handle ipn callback.
	 *
	 * @return void
	 */
	public function handle_ipn_callback() {
		$payment_helper = new Alma_Payment_Helper();
		$payment_helper->handle_ipn_callback();
	}


	/**
	 * Handle customer return.
	 *
	 * @return void
	 */
	public function handle_customer_return($is_in_page = false) {
		$payment_helper = new Alma_Payment_Helper();
		$wc_order       = $payment_helper->handle_customer_return($is_in_page);

		// Redirect user to the order confirmation page.
		$alma_gateway = new Alma_Payment_Gateway_Standard();

		$return_url = $alma_gateway->get_return_url( $wc_order );

		if($is_in_page) {
			return wp_send_json_success(array('url' => $return_url));
		}

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
			$settings = new Alma_Settings();

			$alma_checkout_css = Alma_Assets_Helper::get_asset_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_CSS );
			wp_enqueue_style( 'alma-checkout-page-css', $alma_checkout_css, array(), ALMA_VERSION );

			$alma_checkout_js = Alma_Assets_Helper::get_asset_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_JS );
			wp_enqueue_script( 'alma-checkout-page', $alma_checkout_js, array( 'jquery', 'jquery-ui-core', 'jquery-ui-accordion' ), ALMA_VERSION, true );

			if (
				! empty( $settings->settings['display_in_page'] )
				&& $settings->settings['display_in_page'] == 'yes'
			){
				wp_enqueue_script( 'alma-checkout-in-page-cdn', Alma_Constants_Helper::ALMA_PATH_CHECKOUT_CDN_IN_PAGE_JS, array(), ALMA_VERSION, true );

				$alma_checkout_in_page_js = Alma_Assets_Helper::get_asset_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_IN_PAGE_JS );
				wp_enqueue_script( 'alma-checkout-in-page', $alma_checkout_in_page_js, array( 'jquery', 'jquery-ui-core' ), ALMA_VERSION, true );

				wp_localize_script(
					'alma-checkout-in-page',
					'ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
				);

			}
		}
	}

	public function alma_return_checkout_in_page() {
		$this->handle_customer_return(true);
	}

	public function alma_do_checkout_in_page() {

		if( isset($_POST['fields']) && ! empty($_POST['fields']) ) {

			$order    = new \WC_Order();
			$cart     = WC()->cart;
			$checkout = WC()->checkout;
			$data     = [];

			// Loop through posted data array transmitted via jQuery
			foreach( $_POST['fields'] as $values ){
				// Set each key / value pairs in an array
				$data[$values['name']] = $values['value'];
			}

			$cart_hash          = md5( json_encode( wc_clean( $cart->get_cart_for_session() ) ) . $cart->total );
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

			// Loop through the data array
			foreach ( $data as $key => $value ) {
				// Use WC_Order setter methods if they exist
				if ( is_callable( array( $order, "set_{$key}" ) ) ) {
					$order->{"set_{$key}"}( $value );

					// Store custom fields prefixed with wither shipping_ or billing_
				} elseif ( ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) )
				           && ! in_array( $key, array( 'shipping_method', 'shipping_total', 'shipping_tax' ) ) ) {
					$order->update_meta_data( '_' . $key, $value );
				}
			}

			$order->set_created_via( 'checkout' );
			$order->set_cart_hash( $cart_hash );
			$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', isset($_POST['user_id']) ? $_POST['user_id'] : '' ) );
			$order->set_currency( get_woocommerce_currency() );
			$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
			$order->set_customer_ip_address( \WC_Geolocation::get_ip_address() );
			$order->set_customer_user_agent( wc_get_user_agent() );
			$order->set_customer_note( isset( $data['order_comments'] ) ? $data['order_comments'] : '' );
			$order->set_payment_method( isset( $available_gateways[ $data['payment_method'] ] ) ? $available_gateways[ $data['payment_method'] ]  : $data['payment_method'] );
			$order->set_shipping_total( $cart->get_shipping_total() );
			$order->set_discount_total( $cart->get_discount_total() );
			$order->set_discount_tax( $cart->get_discount_tax() );
			$order->set_cart_tax( $cart->get_cart_contents_tax() + $cart->get_fee_tax() );
			$order->set_shipping_tax( $cart->get_shipping_tax() );
			$order->set_total( $cart->get_total( 'edit' ) );

			$checkout->create_order_line_items( $order, $cart );
			$checkout->create_order_fee_lines( $order, $cart );
			$checkout->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping->get_packages() );
			$checkout->create_order_tax_lines( $order, $cart );
			$checkout->create_order_coupon_lines( $order, $cart );


			do_action( 'woocommerce_checkout_create_order', $order, $data );

			// Save the order.
			$order_id = $order->save();

			do_action( 'woocommerce_checkout_update_order_meta', $order_id, $data );

			// We ignore the nonce verification because process_payment is called after validate_fields.
			$settings = new Alma_Settings();
			$payment_helper = new Alma_Payment_Helper();

			$fee_plan = $settings->build_fee_plan( $_POST[ Alma_Constants_Helper::ALMA_FEE_PLAN_IN_PAGE ] ); // phpcs:ignore WordPress.Security.NonceVerification
			$payment = $payment_helper->create_payments( $order, $fee_plan , true);



		}
		wp_send_json_success(array('payment_id' => $payment->id, 'order_id' => $order_id));

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
			$gateways[] = Alma_Payment_Gateway_In_Page::class;
		}

		$gateways[] = Alma_Payment_Gateway_Standard::class;

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
