<?php
/**
 * Alma_Checkout_Helper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Alma_Logger;

/**
 * Class Alma_Checkout_Helper.
 */
class Alma_Checkout_Helper {


	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Alma_Logger();
	}

	/**
	 * Check if alma_fee_plan is set in POST data and verify wp_nonce.
	 *
	 * @param string $id The gateway id.
	 *
	 * @return null|string
	 */
	public function get_chosen_alma_fee_plan( $id ) {
		if ( WC()->cart === null ) {
			wc_add_notice( '<strong>Fee plan</strong> is required.', Alma_Constants_Helper::ERROR );
			return null;
		}

		return $this->check_nonce( Alma_Constants_Helper::ALMA_FEE_PLAN, Alma_Constants_Helper::CHECKOUT_NONCE . $id );
	}

	/**
	 * Checks nonce in POST data for an Input field.
	 *
	 * @param string $field_name Input action name.
	 * @param string $nonce_name Nonce name.
	 *
	 * @return string|null
	 */
	protected function check_nonce( $field_name, $nonce_name ) {
		if ( isset( $_POST[ $field_name ] )
			&& isset( $_POST[ $nonce_name ] )
			&& wp_verify_nonce( $_POST[ $nonce_name ], $nonce_name ) ) {

			return $_POST[ $field_name ];
		}

		$this->logger->error(
			sprintf(
				'Nonce not found or wrong - FieldName "%s" - NonceName "%s" - FieldName value "%s" - NonceName  value"%s"',
				$field_name,
				$nonce_name,
				json_encode($_POST)
			)
		);

		return null;
	}

	/**
	 * Renders nonce field.
	 *
	 * @param string $id The gateway id.
	 *
	 * @return void
	 */
	public function render_nonce_field( $id ) {
		wp_nonce_field( Alma_Constants_Helper::CHECKOUT_NONCE . $id, Alma_Constants_Helper::CHECKOUT_NONCE . $id );
	}

	/**
	 * AJAX when validating the checkout.
	 * If the payment method used is like "alma_****", then rename it to "alma" and let WC do the payment process.
	 *
	 * @return void
	 */
	public function woocommerce_checkout_process() {
		if ( $this->is_alma_payment_method( $_POST[ Alma_Constants_Helper::PAYMENT_METHOD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$_POST[ Alma_Constants_Helper::PAYMENT_METHOD ] = Alma_Constants_Helper::GATEWAY_ID;
		}
	}

	/**
	 * Check if payment_method is set in POST and is an Alma payment method
	 * then and verify wp_nonce.
	 *
	 * @param string $id The gateway id.
	 *
	 * @return bool
	 */
	public function is_alma_payment_method( $id ) {
		$payment_method = $this->check_nonce( Alma_Constants_Helper::PAYMENT_METHOD, Alma_Constants_Helper::CHECKOUT_NONCE . $id );

		return $payment_method && substr( $payment_method, 0, 4 ) === 'alma';
	}
}
