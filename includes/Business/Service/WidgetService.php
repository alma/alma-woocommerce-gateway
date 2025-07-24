<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\WooCommerce\Helper\WidgetHelper;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

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
	 */
	public function display_widget() {
		$environment         = $this->options_service->get_environment();
		$merchant_id         = $this->options_service->get_merchant_id();
		$fee_plan_list       = $this->fee_plan_service->get_fee_plan_list()->filterEnabled();
		$excluded_categories = $this->options_service->get_option( 'excluded_products_list' );
		$language            = WordPressProxy::get_language();

		// Display widget if page is cart or product page and widget is enabled.
		if ( WooCommerceProxy::is_cart_page() ) {
			// Display widget if widget is enabled and there are no excluded categories.
			$widget_cart_enabled = $this->options_service->get_option( 'widget_cart_enabled' );
			$excluded_categories = empty(
				array_intersect(
					WooCommerceProxy::get_cart_items_categories(),
					$excluded_categories
				)
			);
			$display_widget      = $this->should_display_widget( $widget_cart_enabled, $excluded_categories );

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
			// Display widget if widget is enabled and there are no excluded categories.
			$widget_product_enabled = $this->options_service->get_option( 'widget_product_enabled' );
			$excluded_categories    = empty(
				array_intersect(
					WooCommerceProxy::get_current_product_categories(),
					$excluded_categories
				)
			);
			$display_widget         = $this->should_display_widget( $widget_product_enabled, $excluded_categories );

			// Display the product widget.
			WidgetHelper::display_product_widget(
				$environment,
				$merchant_id,
				WooCommerceProxy::get_current_product_price(),
				$fee_plan_list,
				$language,
				$display_widget
			);

		}
	}

	/**
	 * Check if the widget should be displayed based on the settings and excluded categories.
	 *
	 * @param bool $widget_enabled Whether the widget is enabled in settings.
	 * @param bool $excluded_categories Whether there are excluded categories.
	 *
	 * @return bool True if the widget should be displayed, false otherwise.
	 */
	private function should_display_widget( bool $widget_enabled, bool $excluded_categories ): bool {
		return $widget_enabled && $excluded_categories;
	}
}
