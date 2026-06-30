<?php

namespace Alma\Gateway\Infrastructure\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Service\CollectCmsDataService;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Plugin;

class CollectCmsDataController {

	/**
	 * Register the WooCommerce API endpoint for CMS data collection.
	 * Must be called within the is_configured() block, after setApiConfig().
	 *
	 * @param CollectCmsDataService|null $service Optional service override (used in tests).
	 */
	public static function configure( ?CollectCmsDataService $service = null ): void {
		$service = $service ?? Plugin::get_container()->get( CollectCmsDataService::class );

		EventHelper::addEvent(
			'woocommerce_api_' . CollectCmsDataService::WC_API_ENDPOINT,
			array( $service, 'handle' )
		);
	}
}
