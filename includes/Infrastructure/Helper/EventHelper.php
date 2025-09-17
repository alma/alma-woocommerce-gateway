<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Helper\EventHelperInterface;

class EventHelper implements EventHelperInterface {

	/**
	 * Add an event to the event listener
	 *
	 * @param string   $event The event name
	 * @param callable $callback The callback function
	 * @param int      $priority The priority of the callback
	 * @param int      $acceptedArgs The number of arguments the callback accepts
	 *
	 * @return void
	 */
	public static function addEvent( string $event, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): void {
		add_action( $event, $callback, $priority, $acceptedArgs );
	}

	public function addFilter( string $event, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): void {
		add_filter( $event, $callback, $priority, $acceptedArgs );
	}
}
