<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerceService {
	public static function get_version() {
		return WooCommerceProxy::get_version();
	}
}
