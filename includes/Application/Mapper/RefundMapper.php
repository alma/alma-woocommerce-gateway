<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\RefundDto;
use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Infrastructure\Exception\ParametersException;

class RefundMapper {

	/**
	 * Builds a RefundDto from a WC_Order.
	 *
	 * @param OrderAdapterInterface $order The Order.
	 * @param string|null           $comment The reason of refund.
	 * @param int|null              $amount The amount to refund in cents. Must be a positive integer.
	 *
	 * @return RefundDto The constructed RefundDto.
	 * @throws ParametersException
	 */
	public function buildRefundDto( OrderAdapterInterface $order, ?string $comment = null, ?int $amount = null ): RefundDto {

		$refundDto = ( new RefundDto() )
			->setMerchantReference( $order->getMerchantReference() );
		if ( $comment ) {
			$refundDto->setComment( $comment );
		}
		if ( $amount ) {
			$refundDto->setAmount( $amount );
		}

		return $refundDto;
	}
}
