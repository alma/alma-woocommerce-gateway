<?php
/**
 * IpnException.
 *
 * @since 6.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Business/Exception
 * @namespace Alma\Gateway\Business\Exception
 */

namespace Alma\Gateway\Application\Exception\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Domain\Exception\AlmaException;

/**
 * PaymentServiceException
 */
class PaymentServiceException extends AlmaException {

}
