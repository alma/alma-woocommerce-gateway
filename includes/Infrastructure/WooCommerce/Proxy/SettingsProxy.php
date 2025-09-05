<?php

/**
 * @see https://developer.wordpress.org/plugins/settings/custom-settings-page/
 */

namespace Alma\Gateway\Infrastructure\WooCommerce\Proxy;

use WC_Settings_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Class SettingsProxy to manage WordPress/WooCommerce settings.
 */
class SettingsProxy extends WC_Settings_API {
}
