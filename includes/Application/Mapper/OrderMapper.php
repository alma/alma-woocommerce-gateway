<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\DTO\OrderDto;

class OrderMapper {

	/**
	 * Builds an OrderDto from an Order.
	 *
	 * @param OrderAdapterInterface $order The Order object.
	 *
	 * @return OrderDto The constructed OrderDto.
	 */
	public function buildOrderDto( OrderAdapterInterface $order ): OrderDto {
		return ( new OrderDto() )
			->setMerchantReference( $order->getOrderNumber() )
			->setMerchantUrl( $order->getEditOrderUrl() )
			->setCustomerUrl( $order->getViewOrderUrl() )
			->setComment( $order->getCustomerNote() );
	}
}
