<?php
/**
 * BusinessEventsServiceException.
 *
 * @since 6.0.0
 */

namespace Alma\Gateway\Application\Exception\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Domain\Exception\AlmaException;

/**
 * BusinessEventsServiceException
 */
class BusinessEventsServiceException extends AlmaException {

}
