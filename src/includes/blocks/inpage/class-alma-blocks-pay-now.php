<?php
/**
 * Alma_Blocks_Pay_Now.
 *
 * @since
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce\Blocks\Inpage;

use Alma\Woocommerce\Blocks\Alma_Blocks;
use Alma\Woocommerce\Gateways\Inpage\Alma_Payment_Gateway_Pay_Now;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Alma_Blocks_Pay_Now
 */
class Alma_Blocks_Pay_Now extends Alma_Blocks {
	/**
	 * @var Alma_Payment_Gateway_Pay_Now
	 */
	protected $gateway;


	/**
	 * Paiement Gateway name
	 *
	 * @var string
	 */
	protected $name = Alma_Constants_Helper::GATEWAY_ID_IN_PAGE_PAY_NOW;

	public function initialize() {
		parent::initialize();

		$this->gateway = new Alma_Payment_Gateway_Pay_Now();
	}

	public function get_payment_method_script_handles() {
		if ( ! wp_script_is( 'alma-blocks-integration-ip-pay-now' ) ) {
			$asset_path = Alma_Assets_Helper::get_asset_build_url( 'alma-checkout-blocks-ip-pay-now.asset.php' );

			if ( file_exists( $asset_path ) ) {
				require $asset_path;
			}

			wp_register_script(
				'alma-blocks-integration-ip-pay-now',
				Alma_Assets_Helper::get_asset_build_url( 'alma-checkout-blocks-ip-pay-now.js' ),
				array(
					'wc-blocks-registry',
					'wc-settings',
					'wp-element',
					'wp-html-entities',
					'wp-i18n',
				),
				null,
				true
			);
			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'alma-blocks-integration-ip-pay-now' );

			}
		}

		return array( 'alma-blocks-integration-ip-pay-now' );
	}
}
