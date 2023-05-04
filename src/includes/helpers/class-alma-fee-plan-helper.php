<?php
/**
 * Alma_Fee_Plan_Helper.
 *
 * @since 4.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\API\Entities\FeePlan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Fee_Plan_Helper
 */
class Alma_Fee_Plan_Helper {

	/**
	 * Get the minimum amount.
	 *
	 * @param FeePlan $fee_plan The alma fee plan.
	 * @return int
	 */
	public function get_min_purchase_amount( $fee_plan ) {
		if ( $fee_plan->isPayNow() ) {
			return 100;
		}

		return $fee_plan->min_purchase_amount;
	}
}


