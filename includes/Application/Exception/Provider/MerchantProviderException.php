<?php

namespace Alma\Gateway\Application\Exception\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Domain\Exception\AlmaException;

/**
 * Class MerchantProviderException
 */
class MerchantProviderException extends AlmaException {

}
