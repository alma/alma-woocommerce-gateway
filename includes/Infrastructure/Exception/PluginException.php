<?php

namespace Alma\Gateway\Infrastructure\Exception;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Domain\Exception\AlmaException;

/**
 * Class PluginException
 */
class PluginException extends AlmaException {
}
