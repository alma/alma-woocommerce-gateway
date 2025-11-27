<?php

namespace Alma\Gateway\Infrastructure\Entity;

use Alma\Gateway\Infrastructure\Helper\ShortcodeWidgetHelper;
use Alma\Gateway\Plugin;

class ProductWidget extends AbstractWidget {

	/**
	 * Display the Alma widget using shortcode.
	 */
	public function display() {
		/** @var ShortcodeWidgetHelper $shortcodeWidgetHelper */
		$shortcodeWidgetHelper = Plugin::get_container()->get( ShortcodeWidgetHelper::class );

		$shortcodeWidgetHelper->initProductShortcode( self::WIDGET_CLASS, $this->displayWidget );
		$shortcodeWidgetHelper->displayDefaultProductWidget( self::WIDGET_DEFAULT_CLASS );
	}
}
