<?php

namespace Alma\Gateway\Application\Mapper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\CartItemDto;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Plugin;
use Alma\Plugin\Infrastructure\Adapter\OrderLineAdapterInterface;

class CartItemMapper {

	public function buildCartItemDto( OrderLineAdapterInterface $orderLine ): CartItemDto {

		$product = $orderLine->getProduct();
		/** @var ProductCategoryRepository $productCategoryRepository */
		$productCategoryRepository = Plugin::get_container()->get( ProductCategoryRepository::class );
		$categories                = $productCategoryRepository->findByProductId( $product->getId() );

		return ( new CartItemDto(
			$orderLine->getQuantity(),
			DisplayHelper::price_to_cent( $orderLine->getTotal() )
		) )
			->setSku( $product->getSku() )
			->setTitle( $orderLine->getName() )
			->setQuantity( $orderLine->getQuantity() )
			->setUnitPrice( $product->getPrice() )
			->setLinePrice( $orderLine->getTotal() )
			->setCategories( $categories )
			->setUrl( $product->getPermalink() )
			->setPictureUrl( ContextHelper::getAttachmentUrl( $product->getImageId() ) )
			->setRequiresShipping( $product->needsShipping() );
	}
}
