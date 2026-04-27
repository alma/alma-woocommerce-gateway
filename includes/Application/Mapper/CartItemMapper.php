<?php

namespace Alma\Gateway\Application\Mapper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\CartItemDto;
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
			$orderLine->getTotal(),
			$orderLine->getName()
		) )
			->setSku( $product->getSku() )
			->setUnitPrice( $product->getPrice() )
			->setCategories( $categories )
			->setUrl( $product->getPermalink() )
			->setPictureUrl( ContextHelper::getAttachmentUrl( $product->getImageId() ) )
			->setRequiresShipping( $product->needsShipping() );
	}
}
