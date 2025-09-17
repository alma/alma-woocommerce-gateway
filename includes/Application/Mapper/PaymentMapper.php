<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\AddressDto;
use Alma\API\Application\DTO\PaymentDto;
use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\Entity\FeePlan;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;

class PaymentMapper {

	/**
	 * Builds a PaymentDto from a WC_Order and FeePlan.
	 *
	 * @param AbstractGateway       $gateway
	 * @param OrderAdapterInterface $order The Order.
	 * @param FeePlan               $fee_plan The Fee Plan to apply.
	 *
	 * @return PaymentDto The constructed PaymentDto.
	 */
	public function buildPaymentDto( AbstractGateway $gateway, OrderAdapterInterface $order, FeePlan $fee_plan ): PaymentDto {

		return ( new PaymentDto( $order->getTotal() ) )
			->setInstallmentsCount( $fee_plan->getInstallmentsCount() )
			->setDeferredMonths( $fee_plan->getDeferredMonths() )
			->setDeferredDays( $fee_plan->getDeferredDays() )
			->setCustomData(
				array(
					'order_id'  => $order->getId(),
					'order_key' => $order->getOrderKey(),
				)
			)
			->setIpnCallbackUrl( ContextHelper::getWebhookUrl( IpnHelper::IPN_CALLBACK ) )
			->setReturnUrl( ContextHelper::getWebhookUrl( IpnHelper::CUSTOMER_RETURN ) )
			->setLocale( apply_filters( 'alma_checkout_payment_user_locale', ContextHelper::getLocale() ) )
			->setCart(
				( ( new CartMapper() )->buildCartDetails( $order ) )
			)
			->setBillingAddress(
				( new AddressDto() )
					->setFirstName( $order->getBillingFirstName() )
					->setLastName( $order->getBillingLastName() )
					->setCompany( $order->getBillingCompany() )
					->setLine1( $order->getBillingAddress1() )
					->setLine2( $order->getBillingAddress2() )
					->setPostalCode( $order->getBillingPostcode() )
					->setCity( $order->getBillingCity() )
					->setStateProvince( $order->getBillingState() )
					->setCountry( $order->getBillingCountry() )
					->setEmail( $order->getBillingEmail() )
			)
			->setShippingAddress(
				( new AddressDto() )
					->setFirstName( $order->getShippingFirstName() )
					->setLastName( $order->getShippingLastName() )
					->setCompany( $order->getShippingCompany() )
					->setLine1( $order->getShippingAddress1() )
					->setLine2( $order->getShippingAddress2() )
					->setPostalCode( $order->getShippingPostcode() )
					->setCity( $order->getShippingCity() )
					->setStateProvince( $order->getShippingState() )
					->setCountry( $order->getShippingCountry() )
			)
			->setOrigin( $gateway->get_origin() );
	}
}
