<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Repository\ConfigRepository;

/**
 * Class WordPressHelper.
 */
class WordPressHelper {

	/**
	 * Set the key encryptor for API keys.
	 *
	 * @return void
	 *
	 * @todo move this to a more appropriate place
	 */
	public static function set_key_encryptor() {
		add_filter(
			'pre_update_option_' . ConfigRepository::OPTIONS_KEY,
			array( ConfigService::class, 'encryptKeys' )
		);
	}
}
