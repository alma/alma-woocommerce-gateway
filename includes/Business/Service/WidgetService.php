<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\WooCommerce\Helper\WidgetHelper;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WidgetService {

	private OptionsService $options_service;

	public function __construct( OptionsService $options_service ) {
		$this->options_service = $options_service;
	}

	public function display_widget() {
		$widget_cart_enabled    = $this->options_service->get_option( 'widget_cart_enabled' );
		$widget_product_enabled = $this->options_service->get_option( 'widget_product_enabled' );

		if ( 'yes' === $widget_cart_enabled && WooCommerceProxy::is_cart_page() ) {
			WidgetHelper::display_cart_widget( WooCommerceProxy::get_cart_total() );
		} elseif ( 'yes' === $widget_product_enabled && WooCommerceProxy::is_product_page() ) {
			WidgetHelper::display_product_widget( WooCommerceProxy::get_current_product_price() );
		}
	}
}
