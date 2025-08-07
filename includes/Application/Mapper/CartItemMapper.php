<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Domain\OrderLineInterface;
use Alma\API\DTO\CartItemDto;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WordPressProxy;

class CartItemMapper {

	public function buildCartItemDetails( OrderLineInterface $item ): CartItemDto {

		$product    = $item->getProduct();
		$categories = explode( ',', wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ',' ) ) );

		return ( new CartItemDto(
			$item->getQuantity(),
			DisplayHelper::price_to_cent( $item->getTotal() ),
			WordPressProxy::get_attachment_url( $product->get_image_id() )
		) )
			->setSku( $product->get_sku() )
			->setTitle( $item->getName() )
			->setQuantity( $item->getQuantity() )
			->setUnitPrice( DisplayHelper::price_to_cent( $product->get_price() ) )
			->setLinePrice( DisplayHelper::price_to_cent( $item->getTotal() ) )
			->setCategories( $categories )
			->setUrl( $product->get_permalink() )
			->setPictureUrl( WordPressProxy::get_attachment_url( $product->get_image_id() ) )
			->setRequiresShipping( $product->needs_shipping() );
	}
}
