<?php
/**
 * Alma_Order_Helper.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/models
 * @namespace Alma\Woocommerce\Models
 */

namespace Alma\Woocommerce\Models;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Exceptions\Alma_No_Order_Exception;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;

/**
 * Alma_Order_Helper
 */
class Alma_Order {

	/**
	 * Order
	 *
	 * @var \WC_Order|\WC_Order_Refund
	 */
	private $order;

	/**
	 * Order ID
	 *
	 * @var int
	 */
	private $order_id;

	/**
	 * Constructor.
	 *
	 * @param int         $order_id Order Id.
	 * @param string|null $order_key Order key.
	 *
	 * @throws Alma_No_Order_Exception No order.
	 */
	public function __construct( $order_id, $order_key = null ) {
		$this->order_id = $order_id;
		$this->order    = wc_get_order( $this->order_id );

		if ( ! $this->order && $order_key ) {
			// We have an invalid $order_id, probably because invoice_prefix has changed.
			$this->order_id = wc_get_order_id_by_order_key( $order_key );
			$this->order    = wc_get_order( $order_id );
		}

		if ( ! $this->order || ( $order_key && $this->get_order_key() !== $order_key ) ) {
			throw new Alma_No_Order_Exception( $order_id, $order_key );
		}

		$this->order_id = $order_id;
	}

	/**
	 * Get order key.
	 *
	 * @return string
	 */
	public function get_order_key() {
		return $this->order->get_order_key();
	}

	/**
	 * Payment complete.
	 *
	 * @param string $payment_id Payment Id.
	 *
	 * @return void
	 */
	public function payment_complete( $payment_id ) {
		$this->order->payment_complete( $payment_id );
		wc()->cart->empty_cart();
	}

	/**
	 * Get WC order.
	 *
	 * @return \WC_Order|\WC_Order_Refund
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Get order total.
	 *
	 * @return int
	 */
	public function get_total() {
		return Alma_Tools_Helper::alma_price_to_cents( $this->order->get_total() );
	}

	/**
	 * Get order reference.
	 *
	 * @return string
	 */
	public function get_order_reference() {
		return (string) $this->order->get_order_number();
	}

	/**
	 * Order has billing address.
	 *
	 * @return bool
	 */
	public function has_billing_address() {
		return $this->order->get_billing_address_1() || $this->order->get_billing_address_2();
	}

	/**
	 * Order has shipping address.
	 *
	 * @return bool
	 */
	public function has_shipping_address() {
		return $this->order->get_shipping_address_1() || $this->order->get_shipping_address_2();
	}

	/**
	 * Is it an order for a business customer ?.
	 *
	 * @return bool
	 */
	public function is_business() {
		if ( $this->order->get_billing_company() ) {
			return true;
		}
		return false;
	}

	/**
	 * Gets the business name of the order.
	 *
	 * @return string
	 */
	public function get_business_name() {
		return $this->get_billing_address()['company'];
	}

	/**
	 * Get billing address.
	 *
	 * @return array
	 */
	public function get_billing_address() {
		return array(
			'first_name'          => $this->order->get_billing_first_name(),
			'last_name'           => $this->order->get_billing_last_name(),
			'company'             => $this->order->get_billing_company(),
			'line1'               => $this->order->get_billing_address_1(),
			'line2'               => $this->order->get_billing_address_2(),
			'postal_code'         => $this->order->get_billing_postcode(),
			'city'                => $this->order->get_billing_city(),
			'country'             => $this->order->get_billing_country(),
			'country_sublocality' => null,
			'state_province'      => $this->order->get_billing_state(),
			'email'               => $this->order->get_billing_email(),
			'phone'               => $this->order->get_billing_phone(),
		);
	}

	/**
	 * Get shipping address.
	 *
	 * @return array
	 */
	public function get_shipping_address() {
		return array(
			'first_name'          => $this->order->get_shipping_first_name(),
			'last_name'           => $this->order->get_shipping_last_name(),
			'company'             => $this->order->get_shipping_company(),
			'line1'               => $this->order->get_shipping_address_1(),
			'line2'               => $this->order->get_shipping_address_2(),
			'postal_code'         => $this->order->get_shipping_postcode(),
			'city'                => $this->order->get_shipping_city(),
			'country_sublocality' => null,
			'state_province'      => $this->order->get_shipping_state(),
			'country'             => $this->order->get_shipping_country(),
		);
	}

	/**
	 * Get customer order url.
	 *
	 * @return string
	 */
	public function get_customer_url() {
		return $this->order->get_view_order_url();
	}

	/**
	 * Get merchant order url.
	 *
	 * @return string
	 */
	public function get_merchant_url() {
		$admin_path = 'post.php?post=' . $this->get_id() . '&action=edit';

		if ( version_compare( wc()->version, '3.3.0', '<' ) ) {
			return get_admin_url( null, $admin_path );
		}

		return $this->order->get_edit_order_url();
	}

	/**
	 * Get order ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->order_id;
	}
}
