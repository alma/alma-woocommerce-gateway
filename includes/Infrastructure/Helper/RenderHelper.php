<?php

namespace Alma\Gateway\Infrastructure\Helper;

class RenderHelper
{
	/**
	 * Render a value using a specific hook.
	 *
	 * @param string $hook_name The name of the hook to apply.
	 * @param string $value The value to be rendered.
	 * @param string $args Additional arguments to pass to the hook.
	 *
	 * @return string The rendered value after applying the hook.
	 */
	public static function render( string $hook_name, string $value, string $args ): string {
		return apply_filters( $hook_name, $value, $args );
	}
}