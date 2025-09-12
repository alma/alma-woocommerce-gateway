<?php

namespace Alma\Gateway\Infrastructure\Helper;

class UrlHelper
{
	/**
	 * @param string     $url
	 * @param array|null $protocols
	 * @param string     $_context
	 *
	 * @return string|null
	 */
	public static function escUrl(string $url, array $protocols = null, string $_context = 'display'): ?string {
		return esc_url($url, $protocols, $_context);
	}
}