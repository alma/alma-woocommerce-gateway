<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Entity\FeePlanList;
use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Exception\Service\WidgetServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Infrastructure\Helper\WidgetHelper;
use Alma\Gateway\Infrastructure\Repository\ProductRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WidgetService {

	/** @var ConfigService Service to manage options. */
	private ConfigService $optionsService;

	/** @var FeePlanService Service to manage fee plans. */
	private FeePlanService $feePlanService;

	/** @var ContextHelperInterface Adapter to manage context. */
	private ContextHelperInterface $contextHelper;

	/** @var CartAdapterInterface Adapter to manage the cart. */
	private CartAdapterInterface $cartAdapter;

	/** @var WidgetHelper Helper to manage the widget display. */
	private WidgetHelper $widgetHelper;

	/** @var ExcludedProductsHelper */
	private ExcludedProductsHelper $excludedProductsHelper;

	/** @var ProductRepository */
	private ProductRepository $productRepository;

	public function __construct(
		ConfigService $optionsService,
		FeePlanService $feePlanService,
		ProductRepository $productRepository,
		ContextHelperInterface $contextHelper,
		CartAdapterInterface $cartAdapter,
		WidgetHelper $widgetHelper,
		ExcludedProductsHelper $excludedProductsHelper
	) {
		$this->optionsService         = $optionsService;
		$this->feePlanService         = $feePlanService;
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
	 */
	public function displayWidget() {
		$environment = $this->optionsService->getEnvironment();
		$merchantId  = $this->optionsService->getMerchantId();
		try {
			$feePlanList = $this->feePlanService->getFeePlanList()->filterEnabled();
		} catch ( FeePlanServiceException $e ) {
			throw new WidgetServiceException( $e->getMessage() );
		}
		$excludedCategories = $this->optionsService->getExcludedCategories();
		$language           = $this->contextHelper->getLanguage();

		// Display widget if page is cart or product page and widget is enabled.
		if ( $this->contextHelper->isCartPage() ) {
			// Display widget if widget is enabled and there are no excluded categories.
			$widgetCartEnabled = $this->optionsService->getWidgetCartEnabled();
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

		} elseif ( $this->contextHelper->isProductPage() ) {
			// Get the product
			$product = $this->productRepository->findById( $this->contextHelper->getCurrentProduct() );

			// Display widget if widget is enabled and there are no excluded categories.
			$widgetProductEnabled = $this->optionsService->getWidgetProductEnabled();
			$displayWidget        = $this->shouldDisplayWidget(
				$widgetProductEnabled,
				$this->excludedProductsHelper->canDisplayOnProductPage( $product, $excludedCategories ),
				$feePlanList
			);

			// Display the product widget.
			$this->widgetHelper->displayProductWidget(
				$environment,
				$merchantId,
				DisplayHelper::price_to_cent( $product->getPrice() ),
				$feePlanList,
				$language,
				$displayWidget
			);
		}
	}

	/**
	 * Check if the widget should be displayed based on the settings and excluded categories.
	 *
	 * @param bool        $widgetEnabled Whether the widget is enabled in settings.
	 * @param bool        $excludedCategoriesStatus Whether there are excluded categories.
	 * @param FeePlanList $feePlanList The list of fee plans.
	 *
	 * @return bool True if the widget should be displayed, false otherwise.
	 */
	private function shouldDisplayWidget( bool $widgetEnabled, bool $excludedCategoriesStatus, FeePlanList $feePlanList ): bool {
		return $widgetEnabled && $excludedCategoriesStatus && count( $feePlanList ) > 0;
	}
}
