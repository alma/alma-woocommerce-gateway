<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\RefundDto;
use Alma\API\Domain\Adapter\OrderAdapterInterface;

class RefundMapper {

	/**
	 * Builds a RefundDto from a WC_Order.
	 *
	 * @param int                     $amount The amount to refund.
	 * @param string                  $reason The reason of refund.
	 * @param OrderAdapterInterface   $order The Order.
	 *
	 * @return RefundDto The constructed RefundDto.
	 */
	public function buildRefundDto( int $amount, string $reason, OrderAdapterInterface $order ): RefundDto {

		return ( new RefundDto() )
			->setAmount( $amount )
			->setMerchantReference( $order->getMerchantReference() )
			->setComment( $reason );
	}
}
