<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Adapter\FeePlanListAdapterInterface;
use Alma\Gateway\Application\Exception\Service\WidgetServiceException;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\ShortcodeWidgetHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\ProductRepository;
use Alma\Gateway\Infrastructure\Service\AssetsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WidgetService {

	/** @var string class used by merchant's shortcode to display widget */
	const WIDGET_CLASS = 'alma-widget';

	/** @var string Default class to display widget */
	const WIDGET_DEFAULT_CLASS = 'alma-default-widget';

	/** @var ConfigService Service to manage options. */
	private ConfigService $configService;

	/** @var FeePlanRepository Fee Plan Repository */
	private FeePlanRepository $feePlanRepository;

	/** @var CartAdapterInterface Adapter to manage the cart. */
	private CartAdapterInterface $cartAdapter;

	/** @var ExcludedProductsHelper */
	private ExcludedProductsHelper $excludedProductsHelper;

	/** @var ProductRepository Product Repository */
	private ProductRepository $productRepository;
	private ShortcodeWidgetHelper $shortcodeWidgetHelper;
	private AssetsService $assetsService;

	public function __construct(
		ConfigService $configService,
		FeePlanRepository $feePlanRepository,
		ProductRepository $productRepository,
		CartAdapterInterface $cartAdapter,
		ExcludedProductsHelper $excludedProductsHelper,
		ShortcodeWidgetHelper $shortcodeWidgetHelper,
		AssetsService $assetsService
	) {
		$this->configService          = $configService;
		$this->feePlanRepository      = $feePlanRepository;
		$this->productRepository      = $productRepository;
		$this->cartAdapter            = $cartAdapter;
		$this->excludedProductsHelper = $excludedProductsHelper;
		$this->shortcodeWidgetHelper  = $shortcodeWidgetHelper;
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
	public function displayWidget() {

		// Do not display the widget if blocks are enabled.
		if ( ! $this->configService->isBlocksDisabled() ) {
			return;
		}

		// Display non block widget
		$params      = array();
		$environment = $this->configService->getEnvironment();
		$merchantId  = $this->configService->getMerchantId();
		try {
			$feePlanListAdapter = $this->feePlanRepository->getAll()->filterEnabled();
		} catch ( FeePlanRepositoryException $e ) {
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
				$feePlanListAdapter
			);

			// Display the cart widget.
			$this->shortcodeWidgetHelper->initCartShortcode( self::WIDGET_CLASS, $displayWidget );
			$this->shortcodeWidgetHelper->displayDefaultCartWidget( self::WIDGET_DEFAULT_CLASS );
			$params = $this->addParameters( $environment, $merchantId, $this->cartAdapter->getCartTotal(),
				$feePlanListAdapter, $language );

		} elseif ( ContextHelper::isProductPage() ) {
			// Get the product
			try {
				$product = $this->productRepository->getById( ContextHelper::getCurrentProductId() );
			} catch ( ProductRepositoryException $e ) {
				throw new WidgetServiceException( $e->getMessage() );
			}

			// Display widget if widget is enabled and there are no excluded categories.
			$widgetProductEnabled = $this->configService->getWidgetProductEnabled();
			$displayWidget        = $this->shouldDisplayWidget(
				$widgetProductEnabled,
				$this->excludedProductsHelper->canDisplayOnProductPage( $product, $excludedCategories ),
				$feePlanListAdapter
			);

			// Display the product widget.
			$this->shortcodeWidgetHelper->initProductShortcode( self::WIDGET_CLASS, $displayWidget );
			$this->shortcodeWidgetHelper->displayDefaultProductWidget( self::WIDGET_DEFAULT_CLASS );
			$params = $this->addParameters( $environment, $merchantId, $product->getPrice(), $feePlanListAdapter,
				$language );
		}

		try {
			$this->assetsService->loadWidgetAssets( $params );
		} catch ( AssetsServiceException $e ) {
			throw new WidgetServiceException( $e->getMessage() );
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

	/**
	 * Add the parameters needed for the Alma widget.
	 *
	 * @param string                      $environment The API environment (live or test).
	 * @param string                      $merchantId The merchant ID.
	 * @param int                         $price The price of the product or cart in cents.
	 * @param FeePlanListAdapterInterface $feePlanListAdapter The list of fee plans.
	 * @param string                      $language The language code.
	 *
	 * @return void
	 * @see assets/js/frontend/alma-frontend-widget-implementation.js
	 */
	private function addParameters( string $environment, string $merchantId, int $price, FeePlanListAdapterInterface $feePlanListAdapter, string $language ): array {
		return array(
			'environment'             => $environment,
			'widget_selector'         => sprintf( '.%s', self::WIDGET_CLASS ),
			'widget_default_selector' => sprintf( '.%s', self::WIDGET_DEFAULT_CLASS ),
			'merchant_id'             => $merchantId,
			'price'                   => $price,
			'language'                => $language,
			'fee_plan_list'           => array_map(
				function ( FeePlanAdapter $plan ) {
					return array(
						'installmentsCount' => $plan->getInstallmentsCount(),
						'minAmount'         => $plan->getOverrideMinPurchaseAmount(),
						'maxAmount'         => $plan->getOverrideMaxPurchaseAmount(),
						'deferredDays'      => $plan->getDeferredDays(),
						'deferredMonths'    => $plan->getDeferredMonths(),
					);
				},
				$feePlanListAdapter->getArrayCopy()
			),
			'hide_if_not_eligible'    => false,
			'transition_delay'        => 5500,
			'monochrome'              => true,
			'hide_border'             => false,
		);
	}
}
