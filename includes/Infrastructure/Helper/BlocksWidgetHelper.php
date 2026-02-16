<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Entity\CartWidget;
use Alma\Gateway\Infrastructure\Exception\Entity\CartWidgetException;
use Alma\Gateway\Infrastructure\Exception\Helper\HelperException;
use Alma\Gateway\Plugin;

/**
 * Helper class to display the cart and product widgets with a shortcode.
 * This class manages the display for Classic themes.
 */
class BlocksWidgetHelper {

	public static function registerWidget() {
		/** @var CartWidget $cartWidget */
		$cartWidget = Plugin::get_container()->get( CartWidget::class );
		$cartWidget->register();
	}

	/**
	 * Prepare assets for the widget
	 *
	 * @throws HelperException
	 */
	public static function prepareWidgetAssets() {
		/** @var CartWidget $cartWidget */
		$cartWidget = Plugin::get_container()->get( CartWidget::class );
		try {
			$cartWidget->prepareAssets();
		} catch ( CartWidgetException $e ) {
			throw new HelperException( 'Can not prepare Widget Assets', 0, $e );
		}
	}
}
