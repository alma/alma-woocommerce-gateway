<?php

namespace Alma\Gateway\Business\Service;

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

	public function get_cache( string $cache_key ) {
		return WC()->session->get( $cache_key );
	}

	public function set_cache( string $cache_key, $value ) {
		WC()->session->set( $cache_key, $value );
	}
}
