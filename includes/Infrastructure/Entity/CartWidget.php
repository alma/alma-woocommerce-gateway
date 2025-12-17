<?php

namespace Alma\Gateway\Infrastructure\Entity;

use Alma\Gateway\Infrastructure\Block\Widget\WidgetBlock;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\ShortcodeWidgetHelper;
use Alma\Gateway\Plugin;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

class CartWidget extends AbstractWidget {

	/**
	 * Register the Alma widget.
	 * For Blocks widget, registration is done here.
	 * WooCommerce will handle the display.
	 */
	public function register() {
		if ( ContextHelper::isCartPageUseBlocks() ) {
			register_block_type_from_metadata( AssetsHelper::getBuildPath( 'alma-widget-block' ) );

			add_action(
				'woocommerce_blocks_cart_block_registration',
				function ( IntegrationRegistry $integrationRegistry ) {

					/** @var WidgetBlock $widgetBlock */
					$widgetBlock = Plugin::get_container()->get( WidgetBlock::class );
					$integrationRegistry->register( $widgetBlock );
				}
			);
		}
	}

	/**
	 * For non-blocks widget, register is done at display time.
	 */
	public function display() {

		if ( ! ContextHelper::isCartPageUseBlocks() ) {
			$this->displayShortcodeWidget();
		}
	}

	/**
	 * Display the Alma widget using shortcode.
	 */
	public function displayShortcodeWidget() {
		/** @var ShortcodeWidgetHelper $shortcodeWidgetHelper */
		$shortcodeWidgetHelper = Plugin::get_container()->get( ShortcodeWidgetHelper::class );
		$shortcodeWidgetHelper->initCartShortcode( self::WIDGET_CLASS, $this->displayWidget, $this->noExcludedCategories );
		$shortcodeWidgetHelper->displayDefaultCartWidget( self::WIDGET_DEFAULT_CLASS );
	}
}
