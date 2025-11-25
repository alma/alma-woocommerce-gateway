<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Entity\CartWidget;
use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

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
}
