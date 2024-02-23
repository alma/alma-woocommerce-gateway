<?php
/**
 * Alma_Plugin_Helper.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\Woocommerce\Admin\Helpers\Alma_Check_Legal_Helper;
use Alma\Woocommerce\Alma_Cart_Handler;
use Alma\Woocommerce\Alma_Payment_Upon_Trigger;
use Alma\Woocommerce\Alma_Product_Handler;
use Alma\Woocommerce\Alma_Refund;
use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Alma_Share_Of_Checkout;
use Alma\Woocommerce\Alma_Shortcodes;
use Alma\Woocommerce\Blocks\Standard\Alma_Blocks_Pay_Later;
use Alma\Woocommerce\Blocks\Standard\Alma_Blocks_Pay_More_Than_Four;
use Alma\Woocommerce\Blocks\Standard\Alma_Blocks_Standard;
use Alma\Woocommerce\Blocks\Standard\Alma_Blocks_Pay_Now;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Plugin_Helper
 */
class Alma_Plugin_Helper {

	/**
	 * The order helper
	 *
	 * @var Alma_Order_Helper
	 */
	protected $order_helper;


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->order_helper = new Alma_Order_Helper();
	}

	/**
	 * It’s important to note that adding hooks inside gateway classes may not trigger.
	 * Gateways are only loaded when needed, such as during checkout and on the settings page in admin.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action(
			Alma_Tools_Helper::action_for_webhook( Alma_Constants_Helper::CUSTOMER_RETURN ),
			array(
				$this->order_helper,
				'handle_customer_return',
			)
		);

		add_action(
			Alma_Tools_Helper::action_for_webhook( Alma_Constants_Helper::IPN_CALLBACK ),
			array(
				$this->order_helper,
				'handle_ipn_callback',
			)
		);
	}

	/**
	 * Add the shorcodes and scripts.
	 *
	 * @return void
	 */
	public function add_shortcodes_and_scripts() {
		$settings = new Alma_Settings();

		if (
			$settings->is_enabled()
			&& $settings->is_allowed_to_see_alma( wp_get_current_user() )
		) {

			$this->add_widgets_shortcodes();

			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

			if (
				! empty( $settings->settings['display_in_page'] )
				&& 'yes' === $settings->settings['display_in_page']
			) {
				$this->add_in_page_actions();
			}
		}
	}

	/**
	 * Add the Alma widget shortcode.
	 *
	 * @return void
	 */
	protected function add_widgets_shortcodes() {
		$shortcodes = new Alma_Shortcodes();

		$cart_handler = new Alma_Cart_Handler();
		$shortcodes->init_cart_widget_shortcode( $cart_handler );

		$product_handler = new Alma_Product_Handler();
		$shortcodes->init_product_widget_shortcode( $product_handler );
	}


	/**
	 * Add in page actions.
	 *
	 * @return void
	 */
	protected function add_in_page_actions() {
		add_action( 'wp_ajax_alma_do_checkout_in_page', array( $this->order_helper, 'alma_do_checkout_in_page' ) );
		add_action( 'wp_ajax_nopriv_alma_do_checkout_in_page', array( $this->order_helper, 'alma_do_checkout_in_page' ) );

		add_action( 'wp_ajax_alma_cancel_order_in_page', array( $this->order_helper, 'alma_cancel_order_in_page' ) );
		add_action( 'wp_ajax_nopriv_alma_cancel_order_in_page', array( $this->order_helper, 'alma_cancel_order_in_page' ) );
	}


	/**
	 * Add the wp actions.
	 *
	 * @return void
	 */
	public function add_actions() {
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

		if ( $this->has_woocommerce_blocks() ) {
			add_action(
				'woocommerce_blocks_loaded',
				array(
					$this,
					'alma_register_order_approval_payment_method_type',
				)
			);
		}
	}


	/**
	 * Register the blocks.
	 *
	 * @return void
	 */
	public function alma_register_order_approval_payment_method_type() {

		// Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action.
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				// Register an instance of Alma_Gateway_Blocks.
				$payment_method_registry->register( new Alma_Blocks_Standard() );
				$payment_method_registry->register( new Alma_Blocks_Pay_Now() );
				$payment_method_registry->register( new Alma_Blocks_Pay_Later() );
				$payment_method_registry->register( new Alma_Blocks_Pay_More_Than_Four() );
				$payment_method_registry->register( new \Alma\Woocommerce\Blocks\Inpage\Alma_Blocks_Pay_Now() );
			}
		);
	}

	/**
	 * Is woocommerce block activated ?
	 *
	 * @return bool
	 */
	public function has_woocommerce_blocks() {
		// Check if the required class exists.
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Inject JS in checkout page.
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		if (
			is_checkout()
		) {
			$settings = new Alma_Settings();

			if ( ! $this->has_woocommerce_blocks() ) {
				$this->enqueue_checkout_scripts();
			}

			if (
				! empty( $settings->settings['display_in_page'] )
				&& 'yes' === $settings->settings['display_in_page']
			) {
				$this->enqueue_in_page_scripts();
			}
		}
	}

	/**
	 * Enqueue In page scripts.
	 *
	 * @return void
	 */
	protected function enqueue_checkout_scripts() {
		$alma_checkout_css = Alma_Assets_Helper::get_asset_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_CSS );
		wp_enqueue_style( 'alma-checkout-page-css', $alma_checkout_css, array(), ALMA_VERSION );

		$alma_checkout_js = Alma_Assets_Helper::get_asset_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_JS );
		wp_enqueue_script( 'alma-checkout-page', $alma_checkout_js, array( 'jquery', 'jquery-ui-core', 'jquery-ui-accordion' ), ALMA_VERSION, true );

	}

	/**
	 * Enqueue In page scripts.
	 *
	 * @return void
	 */
	protected function enqueue_in_page_scripts() {
		wp_enqueue_script( 'alma-checkout-in-page-cdn', Alma_Constants_Helper::ALMA_PATH_CHECKOUT_CDN_IN_PAGE_JS, array(), ALMA_VERSION, true );

		if ( $this->add_in_page_actions() ) {
			$alma_checkout_in_page_js = Alma_Assets_Helper::get_asset_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_IN_PAGE_JS );
			wp_enqueue_script(
				'alma-checkout-in-page',
				$alma_checkout_in_page_js,
				array(
					'jquery',
					'jquery-ui-core',
				),
				ALMA_VERSION,
				true
			);

			wp_localize_script(
				'alma-checkout-in-page',
				'ajax_object',
				array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
			);
		}
	}

	/**
	 *  Get the current tab and section.
	 *
	 * @return array
	 */
	public function get_tab_and_section() {
		global $current_tab, $current_section;
		$tab     = $current_tab;
		$section = $current_section;

		if (
			(
				empty( $tab )
				|| empty( $section )
			)
			&& ! empty( $_SERVER['QUERY_STRING'] )
		) {
			$query_parts = explode( '&', $_SERVER['QUERY_STRING'] );

			foreach ( $query_parts as $args ) {
				$query_args = explode( '=', $args );

				if ( count( $query_args ) === 2 ) {
					switch ( $query_args['0'] ) {
						case 'tab':
							$tab = $query_args['1'];
							break;
						case 'section':
							$section = $query_args['1'];
							break;
						default:
							break;
					}
				}
			}
		}

		return array( $tab, $section );
	}
}
