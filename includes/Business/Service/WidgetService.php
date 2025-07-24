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
		$environment            = $this->options_service->get_environment();
		$merchant_id            = $this->options_service->get_merchant_id();
		$widget_cart_enabled    = $this->options_service->get_option( 'widget_cart_enabled' );
		$widget_product_enabled = $this->options_service->get_option( 'widget_product_enabled' );
		$fee_plan_list          = $this->fee_plan_service->get_fee_plan_list()->filterEnabled();
		$excluded_categories    = $this->options_service->get_option( 'excluded_products_list' );
		$language               = WordPressProxy::get_language();

		if ( WooCommerceProxy::is_cart_page() && 'yes' === $widget_cart_enabled ) {
			if ( empty( array_intersect( WooCommerceProxy::get_cart_items_categories(), $excluded_categories ) ) ) {
				WidgetHelper::display_cart_widget(
					$environment,
					$merchant_id,
					WooCommerceProxy::get_cart_total(),
					$fee_plan_list,
					$language
				);
			}
		} elseif ( WooCommerceProxy::is_product_page() && 'yes' === $widget_product_enabled ) {
			// If the product is not in the excluded categories, display the widget.
			if ( empty(
				array_intersect(
					WooCommerceProxy::get_current_product_categories(),
					$excluded_categories
				)
			) ) {
				WidgetHelper::display_product_widget(
					$environment,
					$merchant_id,
					WooCommerceProxy::get_current_product_price(),
					$fee_plan_list,
					$language
				);
			}
		}
	}
}
