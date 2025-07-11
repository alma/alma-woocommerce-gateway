<?php

namespace Alma\Gateway\WooCommerce\Mapper;

use Alma\API\Entities\DTO\OrderDto;
use WC_Order;

class OrderMapper {

	/**
	 * Builds an OrderDto from a WooCommerce order.
	 *
	 * @param WC_Order $wc_order The WooCommerce order object.
	 *
	 * @return OrderDto The constructed OrderDto.
	 */
	public function build_order_dto( WC_Order $wc_order ): OrderDto {
		return ( new OrderDto() )
			->setMerchantReference( $wc_order->get_order_number() )
			->setMerchantUrl( $wc_order->get_edit_order_url() )
			->setCustomerUrl( $wc_order->get_view_order_url() )
			->setComment( $wc_order->get_customer_note() );
	}
}
