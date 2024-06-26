<?php
/**
 * AlmaBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks
 * @namespace Alma\Woocommerce\Blocks;
 */

namespace Alma\Woocommerce\Blocks;

use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\CheckoutHelper;
use Alma\Woocommerce\Helpers\GatewayHelper;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Alma\Woocommerce\Helpers\AssetsHelper;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\PlanBuilderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks
 */
class AlmaBlock extends AbstractPaymentMethodType {

	/**
	 * The settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;

	/**
	 * The Gateway helper.
	 *
	 * @var GatewayHelper
	 */
	protected $gateway_helper;

	/**
	 * The checkout helper.
	 *
	 * @var CheckoutHelper
	 */
	protected $checkout_helper;

	/**
	 * The cart helper.
	 *
	 * @var CartHelper
	 */
	protected $cart_helper;

	/**
	 * The plan builder.
	 *
	 * @var PlanBuilderHelper
	 */
	protected $alma_plan_builder;

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->settings          = get_option( AlmaSettings::OPTIONS_KEY, array() );
		$this->gateway_helper    = new GatewayHelper();
		$this->alma_settings     = new AlmaSettings();
		$this->checkout_helper   = new CheckoutHelper();
		$this->cart_helper       = new CartHelper();
		$this->alma_plan_builder = new PlanBuilderHelper();
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
		$asset_path = AssetsHelper::get_asset_build_url( ConstantsHelper::ALMA_PATH_CHECKOUT_BLOCK_PHP );

		if ( file_exists( $asset_path ) ) {
			require_once $asset_path;
		}

		$alma_checkout_blocks_css = AssetsHelper::get_asset_build_url( ConstantsHelper::ALMA_PATH_CHECKOUT_BLOCK_CSS );
		wp_enqueue_style( 'alma-blocks-integration-css', $alma_checkout_blocks_css, array(), ALMA_VERSION );

		$alma_checkout_blocks_react_components_css = AssetsHelper::get_asset_build_url( ConstantsHelper::ALMA_PATH_CHECKOUT_BLOCK_REACT_COMPONENTS_CSS );
		wp_enqueue_style( 'alma-blocks-integration-react-component-css', $alma_checkout_blocks_react_components_css, array(), ALMA_VERSION );

		wp_register_script(
			'alma-blocks-integration',
			AssetsHelper::get_asset_build_url( ConstantsHelper::ALMA_PATH_CHECKOUT_BLOCK_JS ),
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
	 * @throws \Alma\Woocommerce\Exceptions\AlmaException Exception.
	 */
	public function get_payment_method_data() {

		$gateway_id = $this->get_gateway_id();

		$nonce_value = $this->checkout_helper->create_nonce_value( $gateway_id );

		// We get the eligibilites.
		$eligibilities  = $this->alma_settings->get_cart_eligibilities();
		$eligible_plans = $this->alma_settings->get_eligible_plans_keys_for_cart( $eligibilities, $gateway_id );

		$plans = $this->alma_plan_builder->get_plans_by_keys( $eligible_plans, $eligibilities );

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans );

		$is_in_page = $this->gateway_helper->is_in_page_gateway( $gateway_id );

		$data = array(
			'title'           => $this->gateway_helper->get_alma_gateway_title( $gateway_id, true ),
			'description'     => $this->gateway_helper->get_alma_gateway_description( $gateway_id, true ),
			'gateway_name'    => $gateway_id,
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
