<?php

namespace Alma\Gateway\Infrastructure\Helper;

class CmsHelper {

	public static function getCmsVersion(): array {
		return ['WordPress', get_bloginfo( 'version' )];
	}

	public static function getShopVersion(): array {
		return ['WooCommerce', WC()->version];
	}

}
