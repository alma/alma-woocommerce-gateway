<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Plugin;

/**
 * Class CoreHelper.
 */
class CoreHelper {

	/**
	 * Reload the DI container Options when the plugin Options are updated.
	 * This is useful to ensure that the latest options are used in the application.
	 *
	 * @return void
	 */
	public static function autoReloadOptionsOnOptionSave() {
		add_action(
			'woocommerce_update_options_payment_gateways_alma_config_gateway',
			function () {
				// Reload the DI container Options.
				Plugin::get_container( true )->reloadOptions();
			}
		);
	}
}
