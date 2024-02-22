<?php
/**
 * Alma_Blocks_Standard.
 *
 * @since
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce\Blocks\Standard;

use Alma\Woocommerce\Blocks\Alma_Blocks;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Pay_More_Than_Four;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Standard;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Alma_Blocks_Standard
 */
class Alma_Blocks_Pay_More_Than_Four extends Alma_Blocks {
	/**
	 * @var Alma_Payment_Gateway_Pay_More_Than_Four
	 */
	protected $gateway;



	/**
	 * Paiement Gateway name
	 *
	 * @var string
	 */
	protected $name = Alma_Constants_Helper::GATEWAY_ID_MORE_THAN_FOUR;

	public function initialize() {
		parent::initialize();

		$this->gateway = new Alma_Payment_Gateway_Pay_More_Than_Four();
	}

	public function get_payment_method_script_handles() {
		if ( ! wp_script_is( 'alma-blocks-integration-pay-more-than-four' ) ) {
			$asset_path = Alma_Assets_Helper::get_asset_build_url( 'alma-checkout-blocks-pay-more-than-four.asset.php' );

			if ( file_exists( $asset_path ) ) {
				require $asset_path;
			}

			wp_register_script(
				'alma-blocks-integration-pay-more-than-four',
				Alma_Assets_Helper::get_asset_build_url( 'alma-checkout-blocks-pay-more-than-four.js' ),
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
				wp_set_script_translations( 'alma-blocks-integration-pay-more-than-four' );

			}
		}

		return array( 'alma-blocks-integration-pay-more-than-four' );
	}
}
