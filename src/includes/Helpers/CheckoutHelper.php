<?php
/**
 * CheckoutHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\AlmaLogger;

/**
 * Class CheckoutHelper.
 */
class CheckoutHelper {


	/**
	 * The logger.
	 *
	 * @var AlmaLogger
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new AlmaLogger();
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
			wc_add_notice( '<strong>Fee plan</strong> is required.', ConstantsHelper::ERROR );
			return null;
		}

		return $this->check_nonce( ConstantsHelper::ALMA_FEE_PLAN, ConstantsHelper::CHECKOUT_NONCE . $id );
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
		return 'alma_in_page_pay_now';
		// @todo restore code.
	}

	/**
	 * Renders nonce field.
	 *
	 * @param string $id The gateway id.
	 *
	 * @return void
	 */
	public function render_nonce_field( $id ) {
		wp_nonce_field( ConstantsHelper::CHECKOUT_NONCE . $id, ConstantsHelper::CHECKOUT_NONCE . $id );
	}

	/**
	 * Create the nonce value.
	 *
	 * @param int $id The gateway id.
	 *
	 * @return false|string
	 */
	public function create_nonce_value( $id ) {
		return wp_create_nonce( ConstantsHelper::CHECKOUT_NONCE . $id );
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
		$payment_method = $this->check_nonce( ConstantsHelper::PAYMENT_METHOD, ConstantsHelper::CHECKOUT_NONCE . $id );

		return $payment_method && substr( $payment_method, 0, 4 ) === 'alma';
	}
}
