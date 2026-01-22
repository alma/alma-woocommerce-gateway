<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Entity\WidgetInterface;
use Alma\API\Domain\ValueObject\Environment;
use Alma\Gateway\Application\Exception\Service\WidgetServiceException;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Entity\CartWidget;
use Alma\Gateway\Infrastructure\Entity\ProductWidget;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\ProductRepository;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WidgetService {

	/** @var ConfigService Service to manage options. */
	private ConfigService $configService;

	/** @var FeePlanRepository Fee Plan Repository */
	private FeePlanRepository $feePlanRepository;

	/** @var GatewayRepository Fee Plan Repository */
	private GatewayRepository $gatewayRepository;

	/** @var CartAdapterInterface Adapter to manage the cart. */
	private CartAdapterInterface $cartAdapter;

	/** @var ExcludedProductsHelper */
	private ExcludedProductsHelper $excludedProductsHelper;

	/** @var ProductRepository Product Repository */
	private ProductRepository $productRepository;
	private AssetsService $assetsService;

	public function __construct(
		ConfigService $configService,
		FeePlanRepository $feePlanRepository,
		ProductRepository $productRepository,
		GatewayRepository $gatewayRepository,
		ExcludedProductsHelper $excludedProductsHelper,
		AssetsService $assetsService
	) {
		$this->configService          = $configService;
		$this->feePlanRepository      = $feePlanRepository;
		$this->productRepository      = $productRepository;
		$this->gatewayRepository      = $gatewayRepository;
		$this->cartAdapter            = ContextHelper::getCart();
		$this->excludedProductsHelper = $excludedProductsHelper;
		$this->assetsService          = $assetsService;
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
	public function runWidget() {

		$environment = $this->configService->getEnvironment();
		$merchantId  = $this->configService->getMerchantId();

		// If merchant ID is not defined yet, do not display the widget
		if ( ! $merchantId ) {
			return;
		}

		try {
			$feePlanListAdapter = $this->feePlanRepository->getAll()->filterEnabled()->orderBy( $this->gatewayRepository->findOrderedAlmaGateways() );

		} catch ( FeePlanRepositoryException $e ) {
			throw new WidgetServiceException( $e->getMessage() );
		}
		$excludedCategories = $this->configService->getExcludedCategories();
		$language           = ContextHelper::getLanguage();

		if ( ContextHelper::isAdmin() || ContextHelper::isCartPage() ) {
			$this->displayCartWidget( $excludedCategories, $feePlanListAdapter, $environment, $merchantId,
				$language )->display();
		} elseif ( ContextHelper::isProductPage() ) {
			$this->displayProductWidget( $excludedCategories, $feePlanListAdapter, $environment, $merchantId,
				$language )->display();
		}
	}

	/**
	 * Display Cart Widget if page is cart page and widget is enabled.
	 *
	 * @return void
	 * @throws WidgetServiceException
	 */
	public function displayCartWidget( array $excludedCategories, FeePlanListAdapter $feePlanListAdapter, Environment $environment, string $merchantId, string $language ): WidgetInterface {

		// Display widget if widget is enabled and there are no excluded categories.
		$hasExcludedCategories = ! $this->excludedProductsHelper->canDisplayOnCartPage( $this->cartAdapter,
			$excludedCategories );
		$displayWidget         = $this->shouldDisplayWidget(
			$this->configService->getWidgetCartEnabled(),
			$hasExcludedCategories,
			$feePlanListAdapter
		);
		$price                 = $this->cartAdapter->getCartTotal();

		// Configure the widget
		/** @var CartWidget $widget */
		$widget = ( Plugin::get_container()->get( CartWidget::class ) )
			->configure(
				$feePlanListAdapter,
				$environment,
				$merchantId,
				$price,
				$displayWidget,
				$language,
				$hasExcludedCategories
			);

		// Load widget assets
		try {
			$this->assetsService->registerWidgetAssets( $widget->getConfiguration() );
		} catch ( AssetsServiceException $e ) {
			throw new WidgetServiceException( $e->getMessage() );
		}

		return $widget;
	}

	/**
	 * Display Product Widget if page is product page and widget is enabled.
	 *
	 * @return void
	 * @throws WidgetServiceException
	 */
	public function displayProductWidget( array $excludedCategories, FeePlanListAdapter $feePlanListAdapter, Environment $environment, string $merchantId, string $language ): WidgetInterface {

		// Get the product
		try {
			$product = $this->productRepository->getById( ContextHelper::getCurrentProductId() );
		} catch ( ProductRepositoryException $e ) {
			throw new WidgetServiceException( $e->getMessage() );
		}

		// Display widget if widget is enabled and there are no excluded categories.
		$hasExcludedCategories = ! $this->excludedProductsHelper->canDisplayOnProductPage( $product,
			$excludedCategories );
		$displayWidget         = $this->shouldDisplayWidget(
			$this->configService->getWidgetProductEnabled(),
			$hasExcludedCategories,
			$feePlanListAdapter
		);
		$price                 = $product->getPrice();

		// Configure the widget
		/** @var ProductWidget $widget */
		$widget = ( Plugin::get_container()->get( ProductWidget::class ) )
			->configure(
				$feePlanListAdapter,
				$environment,
				$merchantId,
				$price,
				$displayWidget,
				$language,
				$hasExcludedCategories
			);

		// Load widget assets
		try {
			$this->assetsService->registerWidgetAssets( $widget->getConfiguration() );
		} catch ( AssetsServiceException $e ) {
			throw new WidgetServiceException( $e->getMessage() );
		}

		return $widget;
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
		return $widgetEnabled && ! $excludedCategoriesStatus && count( $feePlanList ) > 0;
	}
}
