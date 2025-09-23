<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\CartItemDto;
use Alma\API\Domain\Adapter\OrderLineAdapterInterface;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Plugin;

class CartItemMapper {

	public function buildCartItemDetails( OrderLineAdapterInterface $orderLine ): CartItemDto {

		$product = $orderLine->getProduct();
		/** @var ProductCategoryRepository $productCategoryRepository */
		$productCategoryRepository = Plugin::get_container()->get( ProductCategoryRepository::class );
		$categories                = $productCategoryRepository->findByProductId( $product->getId() );

		return ( new CartItemDto(
			$orderLine->getQuantity(),
			DisplayHelper::price_to_cent( $orderLine->getTotal() ),
			ContextHelper::getAttachmentUrl( $product->get_image_id() )
		) )
			->setSku( $product->get_sku() )
			->setTitle( $orderLine->getName() )
			->setQuantity( $orderLine->getQuantity() )
			->setUnitPrice( DisplayHelper::price_to_cent( $product->get_price() ) )
			->setLinePrice( DisplayHelper::price_to_cent( $orderLine->getTotal() ) )
			->setCategories( $categories )
			->setUrl( $product->get_permalink() )
			->setPictureUrl( ContextHelper::getAttachmentUrl( $product->get_image_id() ) )
			->setRequiresShipping( $product->needs_shipping() );
	}
}
