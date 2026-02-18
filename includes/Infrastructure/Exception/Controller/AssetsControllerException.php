<?php

namespace Alma\Gateway\Infrastructure\Exception\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Domain\Exception\AlmaException;

/**
 * Class AssetsControllerException
 */
class AssetsControllerException extends AlmaException {
}
