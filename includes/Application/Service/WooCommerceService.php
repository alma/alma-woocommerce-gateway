<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WooCommerceProxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WooCommerceService {
	public static function get_version() {
		return WooCommerceProxy::get_version();
	}
}
