<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Entity\FeePlanList;
use Alma\Gateway\Application\Exception\ContainerException;
use Alma\Gateway\Application\Exception\MerchantServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Infrastructure\WooCommerce\Exception\CoreException;
use Alma\Gateway\Infrastructure\WooCommerce\Helper\WidgetHelper;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WordPressProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Repository\ProductRepository;
use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WidgetService {

	/** @var OptionsService Service to manage options. */
	private OptionsService $options_service;

	/** @var FeePlanService Service to manage fee plans. */
	private FeePlanService $fee_plan_service;

	public function __construct( OptionsService $options_service, FeePlanService $fee_plan_service ) {
		$this->options_service  = $options_service;
		$this->fee_plan_service = $fee_plan_service;
	}

	/**
	 * Display the widget based on:
	 * - The current page type
	 * - The widget settings
	 * - The fee plans available
	 * - The excluded categories
	 *
	 * @return void
	 * @throws ContainerException
	 * @throws MerchantServiceException
	 * @throws CoreException
	 */
	public function display_widget() {
		$environment         = $this->options_service->get_environment();
		$merchant_id         = $this->options_service->get_merchant_id();
		$fee_plan_list       = $this->fee_plan_service->getFeePlanList()->filterEnabled();
		$excluded_categories = $this->options_service->get_excluded_categories();
		$language            = WordPressProxy::get_language();

		// Display widget if page is cart or product page and widget is enabled.
		if ( WooCommerceProxy::is_cart_page() ) {
			// Display widget if widget is enabled and there are no excluded categories.
			$widget_cart_enabled = $this->options_service->get_widget_cart_enabled();
			$display_widget      = $this->should_display_widget(
				$widget_cart_enabled,
				ExcludedProductsHelper::can_display_on_cart_page( $excluded_categories ),
				$fee_plan_list
			);

			// Display the cart widget.
			WidgetHelper::display_cart_widget(
				$environment,
				$merchant_id,
				WooCommerceProxy::get_cart_total(),
				$fee_plan_list,
				$language,
				$display_widget
			);

		} elseif ( WooCommerceProxy::is_product_page() ) {
			// Get the product
			/** @var ProductRepository $product_repository */
			$product_repository = Plugin::get_container()->get( ProductRepository::class );
			$product            = $product_repository->findById( WooCommerceProxy::get_current_product() );

			// Display widget if widget is enabled and there are no excluded categories.
			$widget_product_enabled = $this->options_service->get_widget_product_enabled();
			$display_widget         = $this->should_display_widget(
				$widget_product_enabled,
				ExcludedProductsHelper::can_display_on_product_page( $product, $excluded_categories ),
				$fee_plan_list
			);

			// Display the product widget.
			WidgetHelper::display_product_widget(
				$environment,
				$merchant_id,
				DisplayHelper::price_to_cent($product->getPrice()),
				$fee_plan_list,
				$language,
				$display_widget
			);

		}
	}

	/**
	 * Check if the widget should be displayed based on the settings and excluded categories.
	 *
	 * @param bool        $widget_enabled Whether the widget is enabled in settings.
	 * @param bool        $excluded_categories_status Whether there are excluded categories.
	 * @param FeePlanList $fee_plan_list The list of fee plans.
	 *
	 * @return bool True if the widget should be displayed, false otherwise.
	 */
	private function should_display_widget( bool $widget_enabled, bool $excluded_categories_status, FeePlanList $fee_plan_list ): bool {
		return $widget_enabled && $excluded_categories_status && count( $fee_plan_list ) > 0;
	}
}
