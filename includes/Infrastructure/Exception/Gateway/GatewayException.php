<?php

namespace Alma\Gateway\Infrastructure\Exception\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Domain\Exception\AlmaException;

/**
 * Class GatewayException
 */
class GatewayException extends AlmaException {

}
