<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\Client\Application\DTO\CartDto;
use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;

class CartMapper {

	/**
	 * Builds an CartDto from an Order.
	 *
	 * @param OrderAdapterInterface $order
	 *
	 * @return CartDto The constructed CartDto.
	 */
	public function buildCartDto( OrderAdapterInterface $order ): CartDto {
		$cartDto = new CartDto();
		foreach ( $order->getOrderLines() as $item ) {
			$cartDto->addItem(
				( new CartItemMapper() )->buildCartItemDto( $item )
			);
		}

		return $cartDto;
	}
}
