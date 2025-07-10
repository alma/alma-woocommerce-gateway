<?php

namespace Alma\Gateway\WooCommerce\Mapper;

use Alma\API\Entities\DTO\CartDto;
use WC_Order;

class CartMapper {

	/**
	 * Builds an CartDto from a WooCommerce order.
	 *
	 * @param WC_Order $wc_order The WooCommerce order object.
	 *
	 * @return CartDto The constructed OrderDto.
	 */
	public function build_cart_details( WC_Order $wc_order ): CartDto {
		$cart_dto = new CartDto();
		foreach ( $wc_order->get_items() as $item ) {
			$cart_dto->addItem(
				( new CartItemMapper() )->build_cart_item_details( $item )
			);
		}

		return $cart_dto;
	}
}
