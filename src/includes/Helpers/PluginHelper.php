<?php
/**
 * PluginHelper.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\Woocommerce\Admin\Helpers\CheckLegalHelper;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Handlers\CartHandler;
use Alma\Woocommerce\Handlers\ProductHandler;
use Alma\Woocommerce\Services\PaymentUponTriggerService;
use Alma\Woocommerce\Services\RefundService;
use Alma\Woocommerce\Services\ShareOfCheckoutService;
use Alma\Woocommerce\Blocks\Standard\PayLaterBlock;
use Alma\Woocommerce\Blocks\Standard\PayMoreThanFourBlock;
use Alma\Woocommerce\Blocks\Standard\StandardBlock;
use Alma\Woocommerce\Blocks\Standard\PayNowBlock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PluginHelper
 */
class PluginHelper {


	/**
	 * The order helper
	 *
	 * @var OrderHelper
	 */
	protected $order_helper;

	/**
	 * The block helper
	 *
	 * @var BlockHelper
	 */
	protected $block_helper;


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->order_helper = new OrderHelper();
		$this->block_helper = new BlockHelper();
	}

	/**
	 * It’s important to note that adding hooks inside gateway classes may not trigger.
	 * Gateways are only loaded when needed, such as during checkout and on the settings page in admin.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action(
			ToolsHelper::action_for_webhook( ConstantsHelper::CUSTOMER_RETURN ),
			array(
				$this->order_helper,
				'handle_customer_return',
			)
		);

		add_action(
			ToolsHelper::action_for_webhook( ConstantsHelper::IPN_CALLBACK ),
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
		$settings = new AlmaSettings();

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
		$shortcodes = new ShortcodesHelper();

		$cart_handler = new CartHandler();
		$shortcodes->init_cart_widget_shortcode( $cart_handler );

		$product_handler = new ProductHandler();
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
		$payment_upon_trigger_helper = new PaymentUponTriggerService();
		add_action(
			'woocommerce_order_status_changed',
			array(
				$payment_upon_trigger_helper,
				'woocommerce_order_status_changed',
			),
			10,
			3
		);

		$refund = new RefundService();
		add_action( 'admin_init', array( $refund, 'admin_init' ) );

		$check_legal = new CheckLegalHelper();
		add_action( 'init', array( $check_legal, 'check_share_checkout' ) );

		// Launch the "share of checkout".
		$share_of_checkout = new ShareOfCheckoutService();
		add_action( 'init', array( $share_of_checkout, 'send_soc_data' ) );
		if ( $this->block_helper->has_woocommerce_blocks() ) {
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
				$payment_method_registry->register( new StandardBlock() );
				$payment_method_registry->register( new PayNowBlock() );
				$payment_method_registry->register( new PayLaterBlock() );
				$payment_method_registry->register( new PayMoreThanFourBlock() );
				$payment_method_registry->register( new \Alma\Woocommerce\Blocks\Inpage\PayNowBlock() );
				$payment_method_registry->register( new \Alma\Woocommerce\Blocks\Inpage\InPageBlock() );
				$payment_method_registry->register( new \Alma\Woocommerce\Blocks\Inpage\PayLaterBlock() );
			}
		);
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
			$settings = new AlmaSettings();

			$this->enqueue_checkout_scripts();

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
		$alma_checkout_css = AssetsHelper::get_asset_url( ConstantsHelper::ALMA_PATH_CHECKOUT_CSS );
		wp_enqueue_style( 'alma-checkout-page-css', $alma_checkout_css, array(), ALMA_VERSION );

		$alma_checkout_js = AssetsHelper::get_asset_url( ConstantsHelper::ALMA_PATH_CHECKOUT_JS );
		wp_enqueue_script( 'alma-checkout-page', $alma_checkout_js, array( 'jquery', 'jquery-ui-core', 'jquery-ui-accordion' ), ALMA_VERSION, true );

	}

	/**
	 * Enqueue In page scripts.
	 *
	 * @return void
	 */
	protected function enqueue_in_page_scripts() {
		wp_enqueue_script( 'alma-checkout-in-page-cdn', ConstantsHelper::ALMA_PATH_CHECKOUT_CDN_IN_PAGE_JS, array(), ALMA_VERSION, true );

		if ( ! $this->block_helper->has_woocommerce_blocks() ) {
			$alma_checkout_in_page_js = AssetsHelper::get_asset_url( ConstantsHelper::ALMA_PATH_CHECKOUT_IN_PAGE_JS );
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
