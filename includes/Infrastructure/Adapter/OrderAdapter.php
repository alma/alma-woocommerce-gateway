<?php

namespace Alma\Gateway\Infrastructure\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;
use Alma\Plugin\Infrastructure\Adapter\OrderLineAdapterInterface;
use BadMethodCallException;
use WC_Order;

/**
 * Class OrderAdapter
 *
 * This class adapts the WC_Order object to the OrderAdapterInterface, allowing dynamic calls to WC_Order methods.
 * It provides methods to retrieve order details, update order status, and manage order notes.
 *
 * @method addOrderNote( $note, $is_customer_note = 0, $added_by_user = false ) see WC_Order::add_order_note()
 * @method getItems() see WC_Order::get_items()
 * @method hasStatus( $status ) see WC_Order::has_status()
 * @method updateStatus( $new_status, $note = '', $manual = false ) see WC_Order::update_status()
 */
class OrderAdapter implements OrderAdapterInterface {

	private WC_Order $wcOrder;

	public function __construct( WC_Order $wcOrder ) {
		$this->wcOrder = $wcOrder;
	}

	/**
	 * Dynamic call to all WC_Order methods
	 *
	 * @throws BadMethodCallException if the method does not exist on WC_Order
	 */
	public function __call( string $name, array $arguments ) {
		// Convert camelCase to snake_case
		$snakeCaseName = strtolower( preg_replace( '/(?<!^)([A-Z0-9])/', '_$1', $name ) );

		if ( method_exists( $this->wcOrder, $snakeCaseName ) ) {
			return $this->wcOrder->{$snakeCaseName}( ...$arguments );
		}

		throw new BadMethodCallException( "Method $name (→ $snakeCaseName) does not exists on WC_Order" );
	}

	/**
	 * Get the WC_Order object.
	 *
	 * @return WC_Order
	 */
	public function getWcOrder(): WC_Order {
		return $this->wcOrder;
	}


	/**
	 * Return the order ID.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->wcOrder->get_id();
	}

	public function getOrderKey(): string {
		return $this->wcOrder->get_order_key();
	}

	/**
	 * Get the order number.
	 *
	 * @return string
	 */
	public function getOrderNumber(): string {
		return $this->wcOrder->get_order_number();
	}

	/**
	 * Get the view order URL.
	 *
	 * @return string
	 */
	public function getEditOrderUrl(): string {
		return $this->wcOrder->get_edit_order_url();
	}

	/**
	 * Get the view order URL.
	 *
	 * @return string
	 */
	public function getViewOrderUrl(): string {
		return $this->wcOrder->get_view_order_url();
	}

	/**
	 * Get the customer note.
	 *
	 * @return string
	 */
	public function getCustomerNote(): string {
		return $this->wcOrder->get_customer_note();
	}

	/**
	 * Get the billing state.
	 *
	 * @return string|null
	 */
	public function getBillingState(): ?string {
		return $this->wcOrder->get_billing_state();
	}

	/**
	 * Get the billing first name.
	 *
	 * @return string
	 */
	public function getBillingFirstName(): string {
		return $this->wcOrder->get_billing_first_name();
	}

	/**
	 * Get the billing last name.
	 *
	 * @return string
	 */
	public function getBillingLastName(): string {
		return $this->wcOrder->get_billing_last_name();
	}

	/**
	 * Get the billing email.
	 *
	 * @return string
	 */
	public function getBillingEmail(): string {
		return $this->wcOrder->get_billing_email();
	}

	/**
	 * Get the billing phone.
	 *
	 * @return string
	 */
	public function getBillingPhone(): string {
		return $this->wcOrder->get_billing_phone();
	}

	/**
	 * Get the billing company.
	 *
	 * @return string
	 */
	public function getBillingCompany(): string {
		return $this->wcOrder->get_billing_company();
	}

	/**
	 * Check if the order has a billing address.
	 *
	 * @return bool
	 */
	public function hasBillingAddress(): bool {
		return $this->wcOrder->has_billing_address();
	}


	/**
	 * Get the billing address line 1.
	 *
	 * @return string
	 */
	public function getBillingAddress1(): string {
		return $this->wcOrder->get_billing_address_1();
	}

	/**
	 * Get the billing address line 2.
	 *
	 * @return string
	 */
	public function getBillingAddress2(): string {
		return $this->wcOrder->get_billing_address_2();
	}

	/**
	 * Get the billing postcode.
	 *
	 * @return string
	 */
	public function getBillingPostcode(): string {
		return $this->wcOrder->get_billing_postcode();
	}

	/**
	 * Get the billing city.
	 *
	 * @return string
	 */
	public function getBillingCity(): string {
		return $this->wcOrder->get_billing_city();
	}

	/**
	 * Get the billing country.
	 *
	 * @return string
	 */
	public function getBillingCountry(): string {
		return $this->wcOrder->get_billing_country();
	}

	/**
	 * Get the shipping state.
	 *
	 * @return string
	 */
	public function getShippingState(): string {
		return $this->wcOrder->get_shipping_state();
	}

	/**
	 * Get the shipping first name.
	 *
	 * @return string
	 */
	public function getShippingFirstName(): string {
		return $this->wcOrder->get_shipping_first_name();
	}

	/**
	 * Get the shipping last name.
	 *
	 * @return string
	 */
	public function getShippingLastName(): string {
		return $this->wcOrder->get_shipping_last_name();
	}

	/**
	 * Get the shipping company.
	 *
	 * @return string
	 */
	public function getShippingCompany(): string {
		return $this->wcOrder->get_shipping_company();
	}

	/**
	 * Get the shipping address line 1.
	 *
	 * @return string
	 */
	public function getShippingAddress1(): string {
		return $this->wcOrder->get_shipping_address_1();
	}

	/**
	 * Get the shipping address line 2.
	 *
	 * @return string
	 */
	public function getShippingAddress2(): string {
		return $this->wcOrder->get_shipping_address_2();
	}

	/**
	 * Get the shipping postcode.
	 *
	 * @return string
	 */
	public function getShippingPostcode(): string {
		return $this->wcOrder->get_shipping_postcode();
	}

	/**
	 * Get the shipping city.
	 *
	 * @return string
	 */
	public function getShippingCity(): string {
		return $this->wcOrder->get_shipping_city();
	}

	/**
	 * Get the shipping country.
	 *
	 * @return string
	 */
	public function getShippingCountry(): string {
		return $this->wcOrder->get_shipping_country();
	}

	/**
	 * Check if the order has a shipping address.
	 *
	 * @return bool
	 */
	public function hasShippingAddress(): bool {
		return $this->wcOrder->has_shipping_address();
	}


	/**
	 * Get the lines of the order.
	 * This method retrieves the items from the order and maps them to OrderLineAdapterInterface.
	 *
	 * @return OrderLineAdapterInterface[]
	 */
	public function getOrderLines(): array {
		return array_map(
			fn( $item ) => new OrderLineAdapter( $item ),
			$this->wcOrder->get_items()
		);
	}

	/**
	 * Get the Payment identifier.
	 * This method retrieves the transaction ID of the order, which is used to identify the payment
	 *
	 * @return string The transaction ID of the order.
	 */
	public function getPaymentId(): string {
		return $this->wcOrder->get_transaction_id();
	}

	/**
	 * Get the Payment Method type.
	 * This method retrieves the payment method used for the order.
	 *
	 * @return string The Payment Method type of the order.
	 */
	public function getPaymentMethod(): string {
		return $this->wcOrder->get_payment_method();
	}

	/**
	 * Get the merchant reference.
	 * This method retrieves the order number, which serves as the merchant reference for the order.
	 *
	 * @return string The order number of the order.
	 */
	public function getMerchantReference(): string {
		return $this->wcOrder->get_order_number();
	}

	/**
	 * Check if the order is paid with Alma payment method.
	 * This method checks if the order's payment method is one of the Alma payment methods.
	 *
	 * @return bool True if the order is paid with Alma, false otherwise.
	 */
	public function isPaidWithAlma(): bool {
		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = $gateways[ $this->wcOrder->get_payment_method() ] ?? null;

		if ( $gateway instanceof AbstractGateway ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the order has a transaction ID.
	 * This method checks if the order has a transaction ID set, which is necessary for processing
	 * refunds and other payment-related operations.
	 *
	 * @return bool True if the order has a transaction ID, false otherwise.
	 */
	public function hasATransactionId(): bool {
		if ( ! $this->wcOrder->get_transaction_id() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the order is refundable.
	 * This method checks if the order is paid with Alma and has a transaction ID,
	 * indicating that it can be refunded.
	 *
	 * @return bool True if the order is refundable, false otherwise.
	 */
	public function isRefundable(): bool {
		// Check if the order is paid with Alma and has a transaction ID
		return $this->isPaidWithAlma() && $this->hasATransactionId();
	}

	/**
	 * Get the total amount of the order in cents.
	 *
	 * @return int The total amount of the order in cents.
	 */
	public function getTotal(): int {
		return DisplayHelper::price_to_cent( $this->wcOrder->get_total() );
	}

	public function getTotalRefunded(): int {
		return DisplayHelper::price_to_cent( (float) $this->wcOrder->get_total_refunded() );
	}

	public function paymentComplete( $paymentId ): bool {
		return $this->wcOrder->payment_complete( $paymentId );

	}

	public function update_meta_data( $key, $value, $meta_id = 0 ) {
		return $this->wcOrder->update_meta_data( $key, $value, $meta_id );
	}

	public function save(): int {
		return $this->wcOrder->save();
	}
}
