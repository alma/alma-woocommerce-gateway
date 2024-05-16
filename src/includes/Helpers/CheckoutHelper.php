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
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\PluginFactory;

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
	 * The cart factory.
	 *
	 * @var CartFactory
	 */
	protected $cart_factory;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger       = new AlmaLogger();
		$this->cart_factory = new CartFactory();

	}

	/**
	 * Check if alma_fee_plan is set in POST data and verify wp_nonce.
	 *
	 * @param string $id The gateway id.
	 *
	 * @return null|string
	 */
	public function get_chosen_alma_fee_plan( $id ) {
		if ( $this->cart_factory->get_cart() === null ) {
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
		if (
			isset( $_POST[ $field_name ] )
			&& isset( $_POST[ $nonce_name ] )
			&& wp_verify_nonce( $_POST[ $nonce_name ], $nonce_name )
		) {
			return $_POST[ $field_name ];
		}

		$this->logger->error(
			sprintf(
				'Nonce not found or wrong - FieldName "%s" - NonceName "%s"  - data "%s"',
				$field_name,
				$nonce_name,
				wp_json_encode( $_POST )
			)
		);
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
