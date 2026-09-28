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

		$cartItem = ( new CartItemDto(
			$orderLine->getQuantity(),
			$orderLine->getTotal(),
			$orderLine->getName()
		) )
			->setSku( $product->getSku() )
			->setUnitPrice( $product->getPrice() )
			->setCategories( $categories )
			->setUrl( $product->getPermalink() );

		/*
		 * Products without an image leave picture_url unset. The prefixed
		 * client drops empty values since 3.0.1, but the copy bundled in
		 * 6.2.0 validated them and aborted payment creation (issue #618):
		 * keep the guard here instead of relying on the bundled version.
		 */
		$pictureUrl = ContextHelper::getAttachmentUrl( $product->getImageId() );
		if ( '' !== $pictureUrl ) {
			$cartItem->setPictureUrl( $pictureUrl );
		}

		return $cartItem->setRequiresShipping( $product->needsShipping() );
	}
}
