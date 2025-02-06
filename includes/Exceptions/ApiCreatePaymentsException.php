<?php
/**
 * ApiCreatePaymentsException.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Exceptions
 * @namespace Alma\Woocommerce\Exceptions
 */

namespace Alma\Woocommerce\Exceptions;

use Alma\API\Entities\FeePlan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ApiCreatePaymentsException
 */
class ApiCreatePaymentsException extends AlmaException {

	/**
	 * Constructor.
	 *
	 * @param string  $order_id The order id.
	 * @param FeePlan $fee_plan The fee plans.
	 * @param array   $payload The payload.
	 */
	public function __construct( $order_id, $fee_plan, $payload ) {
		$message = sprintf(
			'Error while creating payment. Order id : "%s", Plan definition : "%s", Payload "%s"',
			$order_id,
			wp_json_encode( $fee_plan ),
			wp_json_encode( $payload )
		);

		parent::__construct( $message );
	}
}
