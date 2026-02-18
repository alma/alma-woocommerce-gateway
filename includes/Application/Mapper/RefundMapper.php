<?php

namespace Alma\Gateway\Application\Mapper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\RefundDto;
use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;

class RefundMapper {

	/**
	 * Builds a RefundDto from a WC_Order.
	 *
	 * @param OrderAdapterInterface $order The Order.
	 * @param string|null           $comment The reason of refund.
	 * @param int|null              $amount The amount to refund in cents. Must be a positive integer.
	 *
	 * @return RefundDto The constructed RefundDto.
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
