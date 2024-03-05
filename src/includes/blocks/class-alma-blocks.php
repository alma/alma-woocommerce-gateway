<?php
/**
 * Alma_Blocks.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce\Blocks;
 */

namespace Alma\Woocommerce\Blocks;

use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Helpers\Alma_Cart_Helper;
use Alma\Woocommerce\Helpers\Alma_Checkout_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Alma_Plan_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks
 */
class Alma_Blocks extends AbstractPaymentMethodType {

	/**
	 * The settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;

	/**
	 * The Gateway helper.
	 *
	 * @var Alma_Gateway_Helper
	 */
	protected $gateway_helper;

	/**
	 * The checkout helper.
	 *
	 * @var Alma_Checkout_Helper
	 */
	protected $checkout_helper;

	/**
	 * The cart helper.
	 *
	 * @var Alma_Cart_Helper
	 */
	protected $cart_helper;

	/**
	 * The plan builder.
	 *
	 * @var Alma_Plan_Builder
	 */
	protected $alma_plan_builder;

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->settings          = get_option( Alma_Settings::OPTIONS_KEY, array() );
		$this->gateway_helper    = new Alma_Gateway_Helper();
		$this->alma_settings     = new Alma_Settings();
		$this->checkout_helper   = new Alma_Checkout_Helper();
		$this->cart_helper       = new Alma_Cart_Helper();
		$this->alma_plan_builder = new Alma_Plan_Builder();
	}

	/**
	 * Is the gateway active.
	 *
	 * @return mixed
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Reggister the script.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		$asset_path = Alma_Assets_Helper::get_asset_build_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_BLOCK_PHP );

		if ( file_exists( $asset_path ) ) {
			require $asset_path;
		}

		$alma_checkout_blocks_css = Alma_Assets_Helper::get_asset_build_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_BLOCK_CSS );
		wp_enqueue_style( 'alma-blocks-integration-css', $alma_checkout_blocks_css, array(), ALMA_VERSION );

		$alma_checkout_blocks_react_components_css = Alma_Assets_Helper::get_asset_build_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_BLOCK_REACT_COMPONENTS_CSS );
		wp_enqueue_style( 'alma-blocks-integration-react-component-css', $alma_checkout_blocks_react_components_css, array(), ALMA_VERSION );

		wp_register_script(
			'alma-blocks-integration',
			Alma_Assets_Helper::get_asset_build_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_BLOCK_JS ),
			array(
				'jquery',
				'jquery-ui-core',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			ALMA_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'alma-blocks-integration' );
		}

		if ( $this->gateway_helper->is_in_page_gateway( $this->gateway->id ) ) {
			wp_localize_script(
				'alma-blocks-integration',
				'ajax_object',
				array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
			);
		}

		return array( 'alma-blocks-integration' );
	}

	/**
	 * Send data to the js.
	 *
	 * @return array
	 * @throws \Alma\Woocommerce\Exceptions\Alma_Exception Exception.
	 */
	public function get_payment_method_data() {

		$nonce_value = $this->checkout_helper->create_nonce_value( $this->gateway->id );

		// We get the eligibilites.
		$eligibilities  = $this->alma_settings->get_cart_eligibilities();
		$eligible_plans = $this->alma_settings->get_eligible_plans_keys_for_cart( $eligibilities, $this->gateway->id );
		$plans          = $this->alma_plan_builder->get_plans_by_keys( $eligible_plans, $eligibilities );

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans );

		$is_in_page = $this->gateway_helper->is_in_page_gateway( $this->gateway->id );

		$data = array(
			'title'           => $this->gateway_helper->get_alma_gateway_title( $this->gateway->id, true ),
			'description'     => $this->gateway_helper->get_alma_gateway_description( $this->gateway->id, true ),
			'gateway_name'    => $this->gateway->id,
			'default_plan'    => $default_plan,
			'plans'           => $plans,
			'nonce_value'     => $nonce_value,
			'label_button'    => __( 'Pay With Alma', 'alma-gateway-for-woocommerce' ),
			'is_in_page'      => $is_in_page,
			'amount_in_cents' => $this->cart_helper->get_total_in_cents(),
		);

		if ( $is_in_page ) {
			$data['merchant_id'] = $this->alma_settings->get_active_merchant_id();
			$data['environment'] = strtoupper( $this->alma_settings->get_environment() );
			$data['locale']      = strtoupper( substr( get_locale(), 0, 2 ) );
		}

		return $data;
	}
}
