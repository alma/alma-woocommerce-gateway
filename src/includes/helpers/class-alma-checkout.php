<?php
/**
 * Alma_Checkout.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma_WC\Helpers
 */

namespace Alma_WC\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma_WC\Alma_Logger;

/**
 * Class Alma_Checkout.
 */
class Alma_Checkout {


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
	 * @return null|string
	 */
	public function get_chosen_alma_fee_plan() {
		if ( WC()->cart === null ) {
			wc_add_notice( '<strong>Fee plan</strong> is required.', Alma_Constants::ERROR );
			return null;
		}

		return $this->check_nonce( 'alma_fee_plan', Alma_Constants::CHECKOUT_NONCE );
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
			'Nonce not found or wrong.',
			array(
				'FieldName' => $field_name,
				'NonceName' => $nonce_name,
			)
		);

		return null;
	}

	/**
	 * Renders nonce field.
	 *
	 * @return void
	 */
	public function render_nonce_field() {
		wp_nonce_field( Alma_Constants::CHECKOUT_NONCE, Alma_Constants::CHECKOUT_NONCE );
	}

	/**
	 * AJAX when validating the checkout.
	 * If the payment method used is like "alma_****", then rename it to "alma" and let WC do the payment process.
	 *
	 * @return void
	 */
	public function woocommerce_checkout_process() {
		if ( $this->is_alma_payment_method() ) {
			$_POST['payment_method'] = Alma_Constants::GATEWAY_ID;
		}
	}

	/**
	 * Check if payment_method is set in POST and is an Alma payment method
	 * then and verify wp_nonce.
	 *
	 * @return bool
	 */
	public function is_alma_payment_method() {
		$payment_method = $this->check_nonce( 'payment_method', Alma_Constants::CHECKOUT_NONCE );

		return $payment_method && substr( $payment_method, 0, 4 ) === 'alma';
	}
}
