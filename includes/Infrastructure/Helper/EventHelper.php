<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Helper\EventHelperInterface;

class EventHelper implements EventHelperInterface {
	public function addEvent( string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): void {
		add_action( $hook, $callback, $priority, $acceptedArgs );
	}

	public function addFilter( string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): void {
		add_filter( $hook, $callback, $priority, $acceptedArgs );
	}
}
