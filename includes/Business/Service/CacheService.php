<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

/**
 * Class CacheService
 *
 * This service is responsible for managing cache operations.
 * It provides methods to set and get cache values.
 * Cache is based on session, so invalidation is not mandatory for now.
 *
 * @todo Implement cache expiration and invalidation logic.
 * @see https://www.php-fig.org/psr/psr-6/
 */
class CacheService {

	private WooCommerceProxy $woocommerce_proxy;

	public function __construct( WooCommerceProxy $woocommerce_proxy ) {
		$this->woocommerce_proxy = $woocommerce_proxy;
	}

	public function get_cache( string $cache_key ) {
		return $this->woocommerce_proxy->get_session( $cache_key );
	}

	public function set_cache( string $cache_key, $value ) {
		$this->woocommerce_proxy->set_session( $cache_key, $value );
	}
}
