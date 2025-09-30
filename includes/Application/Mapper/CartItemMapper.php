<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\CartItemDto;
use Alma\API\Domain\Adapter\OrderLineAdapterInterface;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Plugin;

class CartItemMapper {

	/**
	 * @throws ContainerServiceException
	 */
	public function buildCartItemDetails( OrderLineAdapterInterface $orderLine ): CartItemDto {

		$product = $orderLine->getProduct();
		/** @var ProductCategoryRepository $productCategoryRepository */
		$productCategoryRepository = Plugin::get_container()->get( ProductCategoryRepository::class );
		$categories                = $productCategoryRepository->findByProductId( $product->getId() );

		return ( new CartItemDto(
			$orderLine->getQuantity(),
			DisplayHelper::price_to_cent( $orderLine->getTotal() ),
			ContextHelper::getAttachmentUrl( $product->getImageId() )
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
