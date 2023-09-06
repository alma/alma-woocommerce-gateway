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

use Alma\Woocommerce\Exceptions\Alma_Exception;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Alma_Checkout
 */
class Alma_Checkout extends \WC_Checkout {

	/**
	 * Extends \WC_Checkout.
	 *
	 * @param array $post_fields The post data.
	 *
	 * @return \WC_Order
	 * @throws Alma_Exception The exception.
	 */
	public function process_checkout( $post_fields ) {
		foreach ( $post_fields['fields'] as $values ) {
			// Set each key / value pairs in an array.
			$_POST[ $values['name'] ] = $values['value'];
		}
		$nonce_value = wc_get_var( $_POST['woocommerce-process-checkout-nonce'], wc_get_var( $_POST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
			WC()->session->set( 'refresh_totals', true );
			throw new Alma_Exception( __( 'We were unable to process your order, please try again.', 'alma-gateway-for-woocommerce' ) );
		}

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
		wc_set_time_limit( 0 );

		do_action( 'woocommerce_before_checkout_process' ); // phpcs:ignore

		if ( WC()->cart->is_empty() ) {
			/* translators: %s: shop cart url */
			throw new Alma_Exception( sprintf( __( 'Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'alma-gateway-for-woocommerce' ), esc_url( wc_get_page_permalink( 'shop' ) ) ) );
		}

		do_action( 'woocommerce_checkout_process' ); // phpcs:ignore

		$errors      = new \WP_Error();
		$posted_data = $this->get_posted_data();

		// Update session for customer and totals.
		$this->update_session( $posted_data );

		// Validate posted data and cart items before proceeding.
		$this->validate_checkout( $posted_data, $errors );

		foreach ( $errors->get_error_messages() as $message ) {
			wc_add_notice( $message, 'error' );
		}

		if (
			empty( $posted_data['woocommerce_checkout_update_totals'] )
			&& 0 === wc_notice_count( 'error' )
		) {
			$this->process_customer( $posted_data );
			$order_id = $this->create_order( $posted_data );
			$order    = wc_get_order( $order_id );

			if ( is_wp_error( $order_id ) ) {
				throw new Alma_Exception( $order_id->get_error_message() );
			}

			if ( ! $order ) {
				throw new Alma_Exception( __( 'Unable to create order.', 'alma-gateway-for-woocommerce' ) );
			}

			do_action( 'woocommerce_checkout_order_processed', $order_id, $posted_data, $order ); // phpcs:ignore

			return $order;
		}

		throw new Alma_Exception( 'An error occurred' );
	}

	/**
	 * Validate checkout.
	 *
	 * @param array $data The data.
	 * @param array $errors The errors.
	 *
	 * @return void
	 */
	protected function validate_checkout( &$data, &$errors ) {
		$this->validate_posted_data( $data, $errors );
		$this->check_cart_items();

		if (
			empty( $data['woocommerce_checkout_update_totals'] )
			&& empty( $data['terms'] )
			&& ! empty( $_POST['terms-field'] ) // phpcs:ignore WordPress.Security.NonceVerification
		) { // WPCS: input var ok, CSRF ok.
			$errors->add( 'terms', __( 'Please read and accept the terms and conditions to proceed with your order.', 'alma-gateway-for-woocommerce' ) );
		}

		if ( WC()->cart->needs_shipping() ) {
			$shipping_country = WC()->customer->get_shipping_country();

			if ( empty( $shipping_country ) ) {
				$errors->add( 'shipping', __( 'Please enter an address to continue.', 'alma-gateway-for-woocommerce' ) );
			} elseif ( ! in_array( WC()->customer->get_shipping_country(), array_keys( WC()->countries->get_shipping_countries() ), true ) ) {
				/* translators: %s: shipping location */
				$errors->add( 'shipping', sprintf( __( 'Unfortunately <strong>we do not ship %s</strong>. Please enter an alternative shipping address.', 'alma-gateway-for-woocommerce' ), WC()->countries->shipping_to_prefix() . ' ' . WC()->customer->get_shipping_country() ) );
			} else {
				$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

				foreach ( WC()->shipping->get_packages() as $i => $package ) {
					if ( ! isset( $chosen_shipping_methods[ $i ], $package['rates'][ $chosen_shipping_methods[ $i ] ] ) ) {
						$errors->add( 'shipping', __( 'No shipping method has been selected. Please double check your address, or contact us if you need any help.', 'alma-gateway-for-woocommerce' ) );
					}
				}
			}
		}

		do_action( 'woocommerce_after_checkout_validation', $data, $errors ); // phpcs:ignore
	}

}
