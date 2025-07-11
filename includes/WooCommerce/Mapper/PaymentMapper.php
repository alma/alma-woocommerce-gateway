<?php

namespace Alma\Gateway\WooCommerce\Mapper;

use Alma\API\Entities\DTO\AddressDto;
use Alma\API\Entities\DTO\PaymentDto;
use Alma\API\Entities\FeePlan;
use Alma\Gateway\Business\Service\GatewayService;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use WC_Order;

class PaymentMapper {

	/**
	 * Builds a PaymentDto from a WC_Order and FeePlan.
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 * @param FeePlan  $fee_plan The fee plan to apply.
	 *
	 * @return PaymentDto The constructed PaymentDto.
	 * @todo What about payment upon triggers?
	 */
	public function build_payment_dto( AbstractGateway $gateway, WC_Order $wc_order, FeePlan $fee_plan ): PaymentDto {
		return ( new PaymentDto( WooCommerceProxy::get_order_total( $wc_order->get_id() ) ) )
			->setInstallmentsCount( $fee_plan->getInstallmentsCount() )
			->setDeferredMonths( $fee_plan->getDeferredMonths() )
			->setDeferredDays( $fee_plan->getDeferredDays() )
			->setCustomData(
				array(
					'order_id'  => $wc_order->get_id(),
					'order_key' => $wc_order->get_order_key(),
				)
			)
			->setIpnCallbackUrl( WooCommerceProxy::get_webhook_url( GatewayService::IPN_CALLBACK ) )
			->setReturnUrl( WooCommerceProxy::get_webhook_url( GatewayService::CUSTOMER_RETURN ) )
			->setLocale( apply_filters( 'alma_checkout_payment_user_locale', get_locale() ) )
			->setCart(
				( ( new CartMapper() )->build_cart_details( $wc_order ) )
			)
			->setBillingAddress(
				( new AddressDto() )
					->setFirstName( $wc_order->get_billing_first_name() )
					->setLastName( $wc_order->get_billing_last_name() )
					->setCompany( $wc_order->get_billing_company() )
					->setLine1( $wc_order->get_billing_address_1() )
					->setLine2( $wc_order->get_billing_address_2() )
					->setPostalCode( $wc_order->get_billing_postcode() )
					->setCity( $wc_order->get_billing_city() )
					->setStateProvince( $wc_order->get_billing_state() )
					->setCountry( $wc_order->get_billing_country() )
					->setEmail( $wc_order->get_billing_email() )
			)
			->setShippingAddress(
				( new AddressDto() )
					->setFirstName( $wc_order->get_shipping_first_name() )
					->setLastName( $wc_order->get_shipping_last_name() )
					->setCompany( $wc_order->get_shipping_company() )
					->setLine1( $wc_order->get_shipping_address_1() )
					->setLine2( $wc_order->get_shipping_address_2() )
					->setPostalCode( $wc_order->get_shipping_postcode() )
					->setCity( $wc_order->get_shipping_city() )
					->setStateProvince( $wc_order->get_shipping_state() )
					->setCountry( $wc_order->get_shipping_country() )
			)
			->setOrigin( $gateway->get_origin() );
	}
}
