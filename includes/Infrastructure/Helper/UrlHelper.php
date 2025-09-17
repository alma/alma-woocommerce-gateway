<?php

namespace Alma\Gateway\Infrastructure\Helper;

class UrlHelper {

	/**
	 * Check and clean a URL
	 *
	 * @param string     $url
	 * @param array|null $protocols
	 * @param string     $_context
	 *
	 * @return string|null
	 */
	public static function checkAndCleanUrl( string $url, array $protocols = null, string $_context = 'display' ): ?string {
		return esc_url( $url, $protocols, $_context );
	}

	/**
	 * Get the admin logs URL
	 *
	 * @return string The admin logs URL
	 */
	public static function getAdminLogsUrl(): string {
		return admin_url( 'admin.php?page=wc-status&tab=logs' );
	}
}
