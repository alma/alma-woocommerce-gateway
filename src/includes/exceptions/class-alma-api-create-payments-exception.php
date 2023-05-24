<?php
/**
 * Alma_Api_Create_Payments_Exception.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

use Alma\API\Entities\FeePlan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Api_Create_Payments_Exception
 */
class Alma_Api_Create_Payments_Exception extends Alma_Exception {


	/**
	 * Constructor.
	 *
	 * @param string  $order_id The order id.
	 * @param FeePlan $fee_plan The fee plans.
	 */
	public function __construct( $order_id, $fee_plan ) {
		$message = sprintf(
			'Error while creating payment. Order id : %s, Plan definition : %s',
			$order_id,
			wp_json_encode( $fee_plan )
		);

		parent::__construct( $message );
	}
}
