<?php

namespace Alma\Gateway\Infrastructure\Entity;

use Alma\Gateway\Application\Exception\Entity\ProductWidgetException;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Helper\ShortcodeWidgetHelper;
use Alma\Gateway\Plugin;

class ProductWidget extends AbstractWidget {

	/**
	 * Display the Alma widget using shortcode.
	 *
	 * @throws ProductWidgetException
	 */
	public function display() {
		/** @var ShortcodeWidgetHelper $shortcodeWidgetHelper */
		$shortcodeWidgetHelper = Plugin::get_container()->get( ShortcodeWidgetHelper::class );

		$shortcodeWidgetHelper->initProductShortcode( self::WIDGET_CLASS, $this->displayWidget );
		$shortcodeWidgetHelper->displayDefaultProductWidget( self::WIDGET_DEFAULT_CLASS );

		try {
			// Generate assets parameters
			$params = $this->addParameters(
				$this->environment,
				$this->merchantId,
				$this->price,
				$this->feePlanListAdapter,
				$this->language
			);

			$this->assetsService->loadWidgetAssets( $params );
		} catch ( AssetsServiceException $e ) {
			throw new ProductWidgetException( $e->getMessage() );
		}
	}
}
