<?php

namespace Alma\Gateway\Infrastructure\Service;


use Alma\API\Domain\Helper\SessionHelperInterface;

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

	private SessionHelperInterface $sessionHelper;

	public function __construct( SessionHelperInterface $sessionHelper ) {
		$this->sessionHelper = $sessionHelper;

	}

	public function getCache( string $cache_key ) {
		return $this->sessionHelper->getSession( $cache_key );
	}

	public function setCache( string $cache_key, $value ) {
		$this->sessionHelper->setSession( $cache_key, $value );
	}
}
