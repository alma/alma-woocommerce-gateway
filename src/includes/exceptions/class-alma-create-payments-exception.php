<?php
/**
 * Alma_Create_Payments_Exception.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Create_Payments_Exception
 */
class Alma_Create_Payments_Exception extends Alma_Exception {


	/**
	 * Constructor.
	 */
	public function __construct() {
		$message = __( 'Error while creating payment. No data sent', 'alma-gateway-for-woocommerce' );
		parent::__construct( $message );
	}
}
