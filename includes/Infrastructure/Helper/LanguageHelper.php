<?php

namespace Alma\Gateway\Infrastructure\Helper;

class LanguageHelper {

	/**
	 * @param string $domain
	 * @param string $plugin_path
	 *
	 * @return void
	 */
	public static function loadLanguage( string $domain, string $plugin_path ) {
		add_action(
			'plugins_loaded',
			function () use ( $domain, $plugin_path ) {
				load_plugin_textdomain(
					$domain,
					false,
					$plugin_path . '/languages'
				);
			}
		);
	}

	/**
	 * Translate a string.
	 * The function name is deliberately kept short for simplicity.
	 *
	 * @param string $text
	 * @param string $domain
	 *
	 * @return string
	 * @sonar It's a convention to use __() for translations
	 * @phpcs We pass a variable to __() call because it's a proxy!
	 */
	public static function __( string $text, string $domain = 'default' ): string {
		return __( $text, $domain );
	}
}
