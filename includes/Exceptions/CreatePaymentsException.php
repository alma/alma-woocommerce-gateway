<?php
/**
 * CreatePaymentsException.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreatePaymentsException
 */
class CreatePaymentsException extends AlmaException {


	/**
	 * Constructor.
	 */
	public function __construct() {
		$message = __( 'Error while creating payment. No data sent', 'alma-gateway-for-woocommerce' );
		parent::__construct( $message );
	}
}
