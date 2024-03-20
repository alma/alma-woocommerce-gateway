<?php
/**
 * FormHtmlBuilder.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Admin/Builders
 * @namespace Alma\Woocommerce\Admin\Builders
 */

namespace Alma\Woocommerce\Admin\Builders;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * FormHtmlBuilder.
 */
class FormHtmlBuilder {
	/**
	 * Generates select options key values for allowed fee_plans.
	 *
	 * @param array $allowed_fee_plans The fee plans.
	 *
	 * @return array
	 */
	public static function generate_select_options( $allowed_fee_plans ) {
		$select_options = array();

		foreach ( $allowed_fee_plans as $fee_plan ) {
			$select_label = '';

			if ( $fee_plan->isPnXOnly() ) {
				// translators: %d: number of installments.
				$select_label = sprintf( __( '→ %d-installment payment', 'alma-gateway-for-woocommerce' ), $fee_plan->getInstallmentsCount() );
			}

			if ( $fee_plan->isPayNow() ) {
				// translators: %d: number of installments.
				$select_label = __( '→ Pay Now', 'alma-gateway-for-woocommerce' );
			}

			if ( $fee_plan->isPayLaterOnly() ) {
				$deferred_months = $fee_plan->getDeferredMonths();
				$deferred_days   = $fee_plan->getDeferredDays();

				if ( $deferred_days ) {
					// translators: %d: number of deferred days.
					$select_label = sprintf( __( '→ D+%d-deferred payment', 'alma-gateway-for-woocommerce' ), $deferred_days );
				}

				if ( $deferred_months ) {
					// translators: %d: number of deferred months.
					$select_label = sprintf( __( '→ M+%d-deferred payment', 'alma-gateway-for-woocommerce' ), $deferred_months );
				}
			}

			$select_options[ $fee_plan->getPlanKey() ] = $select_label;
		}

		return $select_options;
	}

	/**
	 * Generates a string with % + € OR only % OR only € (depending on parameters given).
	 * If all fees are <= 0 : return an empty string.
	 *
	 * @param string $translation as description prefix.
	 * @param float  $fee_variable as variable amount (if any).
	 * @param float  $fee_fixed as fixed amount (if any).
	 * @param string $fee_description a description).
	 *
	 * @return string
	 */
	public static function generate_fee_to_pay_description( $translation, $fee_variable, $fee_fixed, $fee_description = '' ) {
		if ( ! $fee_variable && ! $fee_fixed ) {
			return '';
		}

		$fees = '';
		if ( $fee_variable ) {
			$fees .= $fee_variable . '%';
		}

		if ( $fee_fixed ) {
			if ( $fee_variable ) {
				$fees .= ' + ';
			}
			$fees .= $fee_fixed . '€';
		}

		return sprintf( '<br><b>%s</b> %s %s', $translation, $fees, $fee_description );
	}


	/**
	 * Render "title" field type with some special css.
	 *
	 * @param string $title The title text to display.
	 *
	 * @return string
	 */
	public static function render_title( $title ) {
		return '<p style="font-weight:normal;">' . $title . '</p>';
	}


	/**
	 * Product categories options.
	 *
	 * @return array
	 */
	public static function generate_categories_options() {
		$product_categories = get_terms(
			'product_cat',
			array(
				'orderby'    => 'name',
				'order'      => 'asc',
				'hide_empty' => false,
			)
		);

		$options = array();

		foreach ( $product_categories as $category ) {
			$options[ $category->slug ] = $category->name;
		}

		return $options;
	}
}
