<?php

namespace Alma\Gateway\Infrastructure\Entity;

use Alma\Gateway\Application\Exception\Entity\CartWidgetException;
use Alma\Gateway\Infrastructure\Block\Widget\WidgetBlock;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\ShortcodeWidgetHelper;
use Alma\Gateway\Plugin;

class CartWidget extends AbstractWidget {

	/**
	 * @throws CartWidgetException
	 */
	public function display() {

		if ( ContextHelper::isCartPageUseBlocks() ) {
			$this->registerBlockWidget();
		} else {
			$this->displayShortcodeWidget();
		}
	}

	/**
	 * Register the Alma widget block. WooCommerce will handle the display.
	 */
	public function registerBlockWidget() {
		add_action(
			'init',
			function () {
				register_block_type_from_metadata( AssetsHelper::getBuildPath( 'alma-widget-block' ) );
			}
		);
		add_action(
			'woocommerce_blocks_loaded',
			function () {
				add_action(
					'woocommerce_blocks_cart_block_registration',
					function ( $integrationRegistry ) {
						$widgetBlock = Plugin::get_container()->get( WidgetBlock::class );
						$integrationRegistry->register( $widgetBlock );
					}
				);
			}
		);
	}

	/**
	 * Display the Alma widget using shortcode.
	 *
	 * @throws CartWidgetException
	 */
	public function displayShortcodeWidget() {
		/** @var ShortcodeWidgetHelper $shortcodeWidgetHelper */
		$shortcodeWidgetHelper = Plugin::get_container()->get( ShortcodeWidgetHelper::class );
		$shortcodeWidgetHelper->initCartShortcode( self::WIDGET_CLASS, $this->displayWidget );
		$shortcodeWidgetHelper->displayDefaultCartWidget( self::WIDGET_DEFAULT_CLASS );

		try {
			$this->assetsService->loadWidgetAssets( $this->getConfiguration() );
		} catch ( AssetsServiceException $e ) {
			throw new CartWidgetException( $e->getMessage() );
		}
	}
}
