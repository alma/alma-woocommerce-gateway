<?php
/**
 * Alma_Checkout.
 *
 * @since 5.0.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Standard;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Alma_Blocks
 */
class Alma_Blocks extends AbstractPaymentMethodType {
	/**
	 * @var Alma_Payment_Gateway_Standard
	 */
	protected $gateway;

	/**
	 * @var Alma_Gateway_Helper
	 */
	protected $gateway_helper;

	protected $name = 'alma';// your payment gateway name

	public function initialize() {
		$this->settings       = get_option( Alma_Settings::OPTIONS_KEY, array() );
		$this->gateway        = new Alma_Payment_Gateway_Standard();
		$this->gateway_helper = new Alma_Gateway_Helper();
	}

	public function is_active() {
		return $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {

		$alma_checkout_js = Alma_Assets_Helper::get_asset_url( Alma_Constants_Helper::ALMA_PATH_CHECKOUT_BLOCK_JS );

		wp_register_script(
			'alma-blocks-integration',
			$alma_checkout_js,
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
				'jquery',
				'jquery-ui-core',
			),
			null,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'alma-blocks-integration' );

		}
		wp_localize_script(
			'alma-blocks-integrations',
			'alma_settings',
			array(
				'title'       => $this->gateway->get_title(),
				'description' => $this->gateway->get_description(),
			)
		);

		return array( 'alma-blocks-integration' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway_helper->get_alma_gateway_title( $this->gateway->id ),
			'description' => $this->gateway_helper->get_alma_gateway_description( $this->gateway->id ),
		);
	}
}
