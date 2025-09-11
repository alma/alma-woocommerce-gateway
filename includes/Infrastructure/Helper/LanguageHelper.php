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
}
