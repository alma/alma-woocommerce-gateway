<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

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
