<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Exception\Service\WidgetServiceException;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\WidgetHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\ProductRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WidgetService {

	/** @var ConfigService Service to manage options. */
	private ConfigService $configService;

	/** @var FeePlanRepository Fee Plan Repository */
	private FeePlanRepository $feePlanRepository;

	/** @var ContextHelperInterface Adapter to manage context. */
	private ContextHelperInterface $contextHelper;

	/** @var CartAdapterInterface Adapter to manage the cart. */
	private CartAdapterInterface $cartAdapter;

	/** @var WidgetHelper Helper to manage the widget display. */
	private WidgetHelper $widgetHelper;

	/** @var ExcludedProductsHelper */
	private ExcludedProductsHelper $excludedProductsHelper;

	/** @var ProductRepository Product Repository */
	private ProductRepository $productRepository;

	public function __construct(
		ConfigService $configService,
		FeePlanRepository $feePlanRepository,
		ProductRepository $productRepository,
		ContextHelperInterface $contextHelper,
		CartAdapterInterface $cartAdapter,
		WidgetHelper $widgetHelper,
		ExcludedProductsHelper $excludedProductsHelper
	) {
		$this->configService          = $configService;
		$this->feePlanRepository      = $feePlanRepository;
		$this->productRepository      = $productRepository;
		$this->contextHelper          = $contextHelper;
		$this->cartAdapter            = $cartAdapter;
		$this->widgetHelper           = $widgetHelper;
		$this->excludedProductsHelper = $excludedProductsHelper;
	}

	/**
	 * Display the widget based on:
	 * - The current page type
	 * - The widget settings
	 * - The fee plans available
	 * - The excluded categories
	 *
	 * @return void
	 * @throws WidgetServiceException
	 * @throws ProductRepositoryException
	 */
	public function displayWidget() {
		$environment = $this->configService->getEnvironment();
		$merchantId  = $this->configService->getMerchantId();
		try {
			$feePlanList = $this->feePlanRepository->getAll()->filterEnabled();
		} catch ( FeePlanServiceException $e ) {
			throw new WidgetServiceException( $e->getMessage() );
		}
		$excludedCategories = $this->configService->getExcludedCategories();
		$language           = ContextHelper::getLanguage();

		// Display widget if page is cart or product page and widget is enabled.
		if ( ContextHelper::isCartPage() ) {
			// Display widget if widget is enabled and there are no excluded categories.
			$widgetCartEnabled = $this->configService->getWidgetCartEnabled();
			$displayWidget     = $this->shouldDisplayWidget(
				$widgetCartEnabled,
				$this->excludedProductsHelper->canDisplayOnCartPage( $this->cartAdapter, $excludedCategories ),
				$feePlanList
			);

			// Display the cart widget.
			$this->widgetHelper->displayCartWidget(
				$environment,
				$merchantId,
				$this->cartAdapter->getCartTotal(),
				$feePlanList,
				$language,
				$displayWidget
			);

		} elseif ( ContextHelper::isProductPage() ) {
			// Get the product
			$product = $this->productRepository->getById( ContextHelper::getCurrentProductId() );

			// Display widget if widget is enabled and there are no excluded categories.
			$widgetProductEnabled = $this->configService->getWidgetProductEnabled();
			$displayWidget        = $this->shouldDisplayWidget(
				$widgetProductEnabled,
				$this->excludedProductsHelper->canDisplayOnProductPage( $product, $excludedCategories ),
				$feePlanList
			);

			// Display the product widget.
			$this->widgetHelper->displayProductWidget(
				$environment,
				$merchantId,
				$product->getPrice(),
				$feePlanList,
				$language,
				$displayWidget
			);
		}
	}

	/**
	 * Check if the widget should be displayed based on the settings and excluded categories.
	 *
	 * @param bool               $widgetEnabled Whether the widget is enabled in settings.
	 * @param bool               $excludedCategoriesStatus Whether there are excluded categories.
	 * @param FeePlanListAdapter $feePlanList The list of fee plans.
	 *
	 * @return bool True if the widget should be displayed, false otherwise.
	 */
	private function shouldDisplayWidget( bool $widgetEnabled, bool $excludedCategoriesStatus, FeePlanListAdapter $feePlanList ): bool {
		return $widgetEnabled && $excludedCategoriesStatus && count( $feePlanList ) > 0;
	}
}
