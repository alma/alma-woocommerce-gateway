<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Repository\ConfigRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Class WordPressHelper.
 */
class WordPressHelper {
	
	/**
	 * Set the key encryptor for API keys.
	 *
	 * @return void
	 *
	 * @toto move this to a more appropriate place
	 */
	public static function set_key_encryptor() {
		add_filter(
			'pre_update_option_' . ConfigRepository::OPTIONS_KEY,
			array( ConfigService::class, 'encryptKeys' )
		);
	}
}
