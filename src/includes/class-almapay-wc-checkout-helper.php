<?php
/**
 * Alma cart handler
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * class Almapay_WC_Checkout_Helper.
 */
class Almapay_WC_Checkout_Helper {
	const CHECKOUT_NONCE = 'alma_checkout_nonce';

	/**
	 * The logger.
	 *
	 * @var Almapay_WC_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Almapay_WC_Logger();
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

		return $this->check_nonce( 'alma_fee_plan', self::CHECKOUT_NONCE );
	}

	/**
	 * Check if payment_method is set in POST and is an Alma payment method
	 * then and verify wp_nonce.
	 *
	 * @return bool
	 */
	public function is_alma_payment_method() {
		$payment_method = $this->check_nonce( 'payment_method', self::CHECKOUT_NONCE );
		$this->logger->info( sprintf( '%s: %s', __FUNCTION__, $payment_method ) );
		return $payment_method && substr( $payment_method, 0, 5 ) === 'alma_';
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

			$this->logger->info( sprintf( '%s: field:"%s", nonce:"%s", value:"%s"', __FUNCTION__, $field_name, $nonce_name, $_POST[ $field_name ] ) );
			return $_POST[ $field_name ];
		}

		$this->logger->info( sprintf( '%s: %s', __FUNCTION__, 'null' ) );

		return null;
	}

	/**
	 * Renders nonce field.
	 *
	 * @return void
	 */
	public function render_nonce_field() {
		wp_nonce_field( self::CHECKOUT_NONCE, self::CHECKOUT_NONCE );
	}
}
