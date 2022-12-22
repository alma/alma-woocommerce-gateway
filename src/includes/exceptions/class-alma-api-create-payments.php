<?php
/**
 * Alma_Api_Create_Payments.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/exceptions
 * @namespace Alma_WC\Exceptions
 */

namespace Alma_WC\Exceptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Api_Create_Payments
 */
class Alma_Api_Create_Payments extends \Exception {


	/**
	 * Constructor.
	 *
	 * @param string $order_id The order id.
	 * @param array  $fee_plan_definition The fee plans.
	 */
	public function __construct( $order_id, $fee_plan_definition ) {
		$message = sprintf(
			'Error while creating payment. Order id : %s, Plan definition : %s',
			$order_id,
			wp_json_encode( $fee_plan_definition )
		);

		parent::__construct( $message );
	}
}
