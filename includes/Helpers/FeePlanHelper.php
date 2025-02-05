<?php
/**
 * FeePlanHelper.
 *
 * @since 4.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\API\Entities\FeePlan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FeePlanHelper
 */
class FeePlanHelper {

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

	/**
	 * Sort Plans Keys by type first (pnx before pay later), then installments count
	 *
	 * @param string $plan_key_1 The first plan key to compare to.
	 * @param string $plan_key_2 The second plan key to compare to.
	 */
	public function alma_usort_plans_keys( $plan_key_1, $plan_key_2 ) {

		if ( $plan_key_1 === $plan_key_2 ) {
			return 0;
		}
		$match_1 = $this->alma_match_plan_key_pattern( $plan_key_1 );
		$match_2 = $this->alma_match_plan_key_pattern( $plan_key_2 );

		if ( ! $match_1 || ! $match_2 ) {
			return 0;
		}

		if (
			1 === $match_2['installments']
			&& 0 === $match_2['deferred_days']
			&& 0 === $match_2['deferred_months']
		) {
			return 1;
		}

		if (
			(
				1 === $match_1['installments']
				&& 0 === $match_1['deferred_days']
				&& 0 === $match_1['deferred_months']
			)
			|| (
				$match_1['deferred_days'] > 0
				&& $match_2['deferred_months'] > 0
				|| (
					$match_2['deferred_days'] > 0
					&& $match_1['deferred_days'] < $match_2['deferred_days']
				)
			)
			|| $match_1['installments'] < $match_2['installments']
		) {
			return -1;
		}

		return 1;
	}

	/**
	 * Check if a plan key match our pattern (then return array with chunks of the pattern)
	 * chunks are :
	 * - key
	 * - kind
	 * - installments
	 * - deferred_days
	 * - deferred_months
	 *
	 * @param string $plan_key The plan key to test.
	 * @param string $regex The regex.
	 *
	 * @return false|array
	 */
	public function alma_match_plan_key_pattern( $plan_key, $regex = ConstantsHelper::SORT_PLAN_KEY_REGEX ) {
		$matches = array();
		if ( preg_match( $regex, $plan_key, $matches ) ) {
			return array_combine(
				array(
					'key',
					'kind',
					'installments',
					'deferred_days',
					'deferred_months',
				),
				$matches
			);
		}

		return false;
	}
}


