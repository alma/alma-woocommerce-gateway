<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\CartItemDto;
use Alma\API\Domain\Adapter\OrderLineAdapterInterface;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;

class CartItemMapper {

	public function buildCartItemDetails( OrderLineAdapterInterface $item ): CartItemDto {

		$product    = $item->getProduct();
		$categories = explode( ',', wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ',' ) ) );

		return ( new CartItemDto(
			$item->getQuantity(),
			DisplayHelper::price_to_cent( $item->getTotal() ),
			ContextHelper::getAttachmentUrl( $product->get_image_id() )
		) )
			->setSku( $product->get_sku() )
			->setTitle( $item->getName() )
			->setQuantity( $item->getQuantity() )
			->setUnitPrice( DisplayHelper::price_to_cent( $product->get_price() ) )
			->setLinePrice( DisplayHelper::price_to_cent( $item->getTotal() ) )
			->setCategories( $categories )
			->setUrl( $product->get_permalink() )
			->setPictureUrl( ContextHelper::getAttachmentUrl( $product->get_image_id() ) )
			->setRequiresShipping( $product->needs_shipping() );
	}
}
