<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Domain\OrderInterface;
use Alma\API\DTO\CartDto;

class CartMapper {

	/**
	 * Builds an CartDto from an Order.
	 *
	 * @param OrderInterface $order
	 *
	 * @return CartDto The constructed OrderDto.
	 */
	public function buildCartDetails( OrderInterface $order ): CartDto {
		$cartDto = new CartDto();
		foreach ( $order->getOrderLines() as $item ) {
			$cartDto->addItem(
				( new CartItemMapper() )->buildCartItemDetails( $item )
			);
		}

		return $cartDto;
	}
}
