<?php

namespace Alma\Gateway\WooCommerce\Mapper;

use Alma\API\Entities\DTO\CartItemDto;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

class CartItemMapper {

	public function build_cart_item_details( \WC_Order_Item $item ): CartItemDto {

		$product    = $item->get_product();
		$categories = explode( ',', wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ',' ) ) );

		return ( new CartItemDto(
			$item->get_quantity(),
			WooCommerceProxy::price_to_cent( $item->get_total() ),
			WordPressProxy::get_attachment_url( $product->get_image_id() )
		) )
			->setSku( $product->get_sku() )
			->setTitle( $item->get_name() )
			->setQuantity( $item->get_quantity() )
			->setUnitPrice( WooCommerceProxy::price_to_cent( $product->get_price() ) )
			->setLinePrice( WooCommerceProxy::price_to_cent( $item->get_total() ) )
			->setCategories( $categories )
			->setUrl( $product->get_permalink() )
			->setPictureUrl( WordPressProxy::get_attachment_url( $product->get_image_id() ) )
			->setRequiresShipping( $product->needs_shipping() );
	}
}
