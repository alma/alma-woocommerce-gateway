<?php
/**
 * Alma_No_Order.
 *
 * @since 4.0.0
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
 * Alma_No_Order
 */
class Alma_No_Order extends \Exception {


	/**
	 * Constructor.
	 *
	 * @param string $order_id The order id.
	 * @param string $order_key The order key.
	 */
	public function __construct( $order_id, $order_key ) {

		parent::__construct( sprintf( "Can't find order '%s' (key: %s). Order Keys do not match.", $order_id, $order_key ) );
	}
}
