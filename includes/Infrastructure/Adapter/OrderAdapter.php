<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use BadMethodCallException;
use WC_Order;

/**
 * Class OrderAdapter
 *
 * This class adapts the WC_Order object to the OrderAdapterInterface, allowing dynamic calls to WC_Order methods.
 * It provides methods to retrieve order details, update order status, and manage order notes.
 *
 * @method getId() see WC_Order::get_id()
 * @method getItems() see WC_Order::get_items()
 * @method getOrderKey() see WC_Order::get_order_key()
 * @method getOrderNumber() see WC_Order::get_order_number()
 * @method getViewOrderUrl() see WC_Order::get_view_order_url()
 * @method getEditOrderUrl() see WC_Order::get_edit_order_url()
 * @method getCustomerNote() see WC_Order::get_customer_note()
 * @method getBillingFirstName() see WC_Order::get_billing_first_name()
 * @method getBillingLastName() see WC_Order::get_billing_last_name()
 * @method getBillingCompany() see WC_Order::get_billing_company()
 * @method hasBillingAddress()) see WC_Order::has_billing_address()
 * @method getBillingAddress1() see WC_Order::get_billing_address_1()
 * @method getBillingAddress2() see WC_Order::get_billing_address_2()
 * @method getBillingPostcode() see WC_Order::get_billing_postcode()
 * @method getBillingCity() see WC_Order::get_billing_city()
 * @method getBillingState() see WC_Order::get_billing_state()
 * @method getBillingCountry() see WC_Order::get_billing_country()
 * @method getBillingEmail() see WC_Order::get_billing_email()
 * @method getBillingPhone() see WC_Order::get_billing_phone()
 * @method getShippingFirstName() see WC_Order::get_shipping_first_name()
 * @method getShippingLastName() see WC_Order::get_shipping_last_name()
 * @method getShippingCompany() see WC_Order::get_shipping_company()
 * @method hasShippingAddress() see WC_Order::has_shipping_address()
 * @method getShippingAddress1() see WC_Order::get_shipping_address_1()
 * @method getShippingAddress2() see WC_Order::get_shipping_address_2()
 * @method getShippingPostcode() see WC_Order::get_shipping_postcode()
 * @method getShippingCity() see WC_Order::get_shipping_city()
 * @method getShippingState() see WC_Order::get_shipping_state()
 * @method getShippingCountry() see WC_Order::get_shipping_country()
 * @method updateMetaData() see WC_Abstract_Order::update_meta_data()
 * @method save() see WC_Order::save()
 */
class OrderAdapter implements OrderAdapterInterface {

	private WC_Order $wc_order;

	public function __construct( $order ) {
		$this->wc_order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	}

	/**
	 * Dynamic call to all WC_Order methods
	 */
	public function __call( string $name, array $arguments ) {
		// Convert camelCase to snake_case
		$snake_case_name = strtolower( preg_replace( '/(?<!^)([A-Z0-9])/', '_$1', $name ) );

		if ( method_exists( $this->wc_order, $snake_case_name ) ) {
			return $this->wc_order->{$snake_case_name}( ...$arguments );
		}

		throw new BadMethodCallException( "Method $name (→ $snake_case_name) does not exists on WC_Order" );
	}

	/**
	 * Get the WC_Order object.
	 *
	 * @return WC_Order
	 */
	public function getWcOrder(): WC_Order {
		return $this->wc_order;
	}

	/**
	 * Get the lines of the order.
	 * This method retrieves the items from the order and maps them to OrderLineAdapterInterface.
	 *
	 * @return OrderLineAdapter[]
	 */
	public function getOrderLines(): array {
		return array_map(
			fn( $item ) => new OrderLineAdapter( $item ),
			$this->wc_order->get_items()
		);
	}

	/**
	 * Get the Payment identifier.
	 * This method retrieves the transaction ID of the order, which is used to identify the payment
	 *
	 * @return string The transaction ID of the order.
	 */
	public function getPaymentId(): string {
		return $this->wc_order->get_transaction_id();
	}

	/**
	 * Get the merchant reference.
	 * This method retrieves the order number, which serves as the merchant reference for the order.
	 *
	 * @return string The order number of the order.
	 */
	public function getMerchantReference(): string {
		return $this->wc_order->get_order_number();
	}

	/**
	 * Get the remaining refund amount.
	 * This method calculates the remaining refund amount by subtracting the total refunded amount from
	 * the total order amount.
	 *
	 * @return float The remaining refund amount.
	 */
	public function getRemainingRefundAmount(): float {
		return $this->get_total() - $this->get_total_refunded();
	}

	/**
	 * Check if the order is fully refunded.
	 * This method checks if the remaining refund amount is zero, indicating that the order has been
	 * fully refunded.
	 *
	 * @return bool True if the order is fully refunded, false otherwise.
	 */
	public function isFullyRefunded(): bool {
		return round( $this->getRemainingRefundAmount(), 3 ) === round( 0, 3 );
	}

	/**
	 * Check if the order is paid with Alma payment method.
	 * This method checks if the order's payment method is one of the Alma payment methods.
	 *
	 * @return bool True if the order is paid with Alma, false otherwise.
	 */
	public function isPaidWithAlma(): bool {
		$gateways = WC()->payment_gateways()->payment_gateways();
		$gateway  = $gateways[ $this->wc_order->get_payment_method() ] ?? null;

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
		if ( ! $this->wc_order->get_transaction_id() ) {
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
	 * Get the order total in cents.
	 *
	 * @param string $orderId
	 *
	 * @return int The order total in cents.
	 */
	public function getOrderTotal( string $orderId ): int {
		return DisplayHelper::price_to_cent( wc_get_order( $orderId )->get_total() );
	}
}
