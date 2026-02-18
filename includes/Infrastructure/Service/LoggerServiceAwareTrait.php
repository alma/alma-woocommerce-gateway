<?php

namespace Alma\Gateway\Infrastructure\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Plugin;
use Psr\Log\LoggerInterface;

trait LoggerServiceAwareTrait {

	protected function getLogger(): LoggerInterface {
		/** @var LoggerInterface $logger */
		$logger = Plugin::get_container()->get( LoggerService::class );

		return $logger;
	}
}
