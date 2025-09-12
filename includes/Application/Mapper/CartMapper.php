<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\CartDto;
use Alma\API\Domain\Adapter\OrderAdapterInterface;

class CartMapper {

	/**
	 * Builds an CartDto from an Order.
	 *
	 * @param OrderAdapterInterface $order
	 *
	 * @return CartDto The constructed OrderDto.
	 */
	public function buildCartDetails( OrderAdapterInterface $order ): CartDto {
		$cartDto = new CartDto();
		foreach ( $order->getOrderLines() as $item ) {
			$cartDto->addItem(
				( new CartItemMapper() )->buildCartItemDetails( $item )
			);
		}

		return $cartDto;
	}
}
