<?php
/**
 * Alma_WC_Helper_Checkout.
 *
 * @since 4.0.0
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Class Alma_WC_Helper_Checkout.
 */
class Alma_WC_Helper_Checkout {

	/**
	 * The logger.
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Alma_WC_Logger();
	}

	/**
	 * Check if alma_fee_plan is set in POST data and verify wp_nonce.
	 *
	 * @return null|string
	 */
	public function get_chosen_alma_fee_plan() {
		if ( WC()->cart === null ) {
			wc_add_notice( '<strong>Fee plan</strong> is required.', 'error' );
			return null;
		}

		return $this->check_nonce( 'alma_fee_plan', Alma_WC_Helper_Constants::CHECKOUT_NONCE );
	}

	/**
	 * Check if payment_method is set in POST and is an Alma payment method
	 * then and verify wp_nonce.
	 *
	 * @return bool
	 */
	public function is_alma_payment_method() {
		$payment_method = $this->check_nonce( 'payment_method', Alma_WC_Helper_Constants::CHECKOUT_NONCE );

		return $payment_method && substr( $payment_method, 0, 4 ) === 'alma';
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
		wp_nonce_field( Alma_WC_Helper_Constants::CHECKOUT_NONCE, Alma_WC_Helper_Constants::CHECKOUT_NONCE );
	}

	/**
	 * AJAX when validating the checkout.
	 * If the payment method used is like "alma_****", then rename it to "alma" and let WC do the payment process.
	 *
	 * @return void
	 */
	public function woocommerce_checkout_process() {
		if ( $this->is_alma_payment_method() ) {
			$_POST['payment_method'] = Alma_WC_Helper_Constants::GATEWAY_ID;
		}
	}
}
