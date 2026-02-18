<?php

namespace Alma\Gateway\Infrastructure\Exception\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Domain\Exception\AlmaException;

/**
 * Class CheckoutServiceException
 */
class CheckoutServiceException extends AlmaException {
}
