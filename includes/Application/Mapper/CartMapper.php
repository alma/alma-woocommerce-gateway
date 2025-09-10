<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\DTO\CartDto;

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
