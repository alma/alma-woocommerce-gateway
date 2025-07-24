<?php

namespace Alma\Gateway\Business\Helper;

use Alma\API\Entities\FeePlan;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class L10nHelper {

	const ALMA_L10N_DOMAIN = 'alma-gateway-for-woocommerce';

	/**
	 * Translate a string.
	 * The function name is deliberately kept short for simplicity.
	 *
	 * @param string $translation
	 * @param string $domain
	 *
	 * @return string
	 * @sonar It's a convention to use __() for translations
	 * @phpcs We pass a variable to __() call because it's a proxy!
	 */
	public static function __( string $translation, string $domain = self::ALMA_L10N_DOMAIN ): string /* NOSONAR */ {
		return __( $translation, $domain );// phpcs:ignore
	}

	/**
	 * Load the plugin language files.
	 *
	 * @param $language_path
	 *
	 * @return void
	 */
	public static function load_language( $language_path ) {
		HooksProxy::load_language( self::ALMA_L10N_DOMAIN, $language_path );
	}

	/**
	 * Generate a title and a toggle description for the fee plan.
	 *
	 * @param FeePlan $fee_plan
	 * @param string  $environment
	 *
	 * @return array
	 * @todo should we move this to FeePlan Oblect?
	 */
	public static function generate_fee_plan_display_data( FeePlan $fee_plan, string $environment ): array {
		$installments    = $fee_plan->getInstallmentsCount();
		$deferred_days   = $fee_plan->getDeferredDays();
		$deferred_months = $fee_plan->getDeferredMonths();
		$min             = DisplayHelper::price_to_euro( $fee_plan->getMinPurchaseAmount( true ) );
		$max             = DisplayHelper::price_to_euro( $fee_plan->getMaxPurchaseAmount( true ) );

		$section_title = '';
		$toggle_label  = '';
		$you_can_offer = '';

		if ( $fee_plan->isPayNow() ) {
			$section_title = self::__( '→ Pay Now' );
			$toggle_label  = sprintf(
				self::__( 'Enable %d-installment payments with Alma' ),
				$installments
			);
			$you_can_offer = sprintf(
				self::__( 'You can offer instant payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
				$installments,
				$min,
				$max
			);
		} elseif ( $fee_plan->isPnXOnly() || $fee_plan->isCredit() ) {
			$section_title = sprintf(
				self::__( '→ %d-installment payment' ),
				$installments
			);
			$toggle_label  = sprintf(
				self::__( 'Enable %d-installment payments with Alma' ),
				$installments
			);
			$you_can_offer = sprintf(
				self::__( 'You can offer %1$d-installment payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
				$installments,
				$min,
				$max
			);
		} elseif ( $fee_plan->isPayLaterOnly() ) {
			if ( $deferred_days > 0 ) {
				$section_title = sprintf( self::__( '→ D+%d-deferred payment' ), $deferred_days );
				$toggle_label  = sprintf( self::__( 'Enable D+%d-deferred payments with Alma' ), $deferred_days );
				$you_can_offer = sprintf(
					self::__( 'You can offer D+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
					$deferred_days,
					$min,
					$max
				);
			} elseif ( $deferred_months > 0 ) {
				$section_title = sprintf( self::__( '→ M+%d-deferred payment' ), $deferred_months );
				$toggle_label  = sprintf(
					self::__( 'Enable M+%d-deferred payments with Alma' ),
					$deferred_months
				);
				$you_can_offer = sprintf(
					self::__( 'You can offer M+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
					$deferred_months,
					$min,
					$max
				);
			}
		}

		$fees_applied          = self::__( 'Fees applied to each transaction for this plan:' );
		$you_pay               = self::generate_fee_to_pay_description(
			self::__( 'You pay:' ),
			$fee_plan->getMerchantFeeVariable() / 100,
			$fee_plan->getMerchantFeeFixed() / 100
		);
		$customer_pays         = self::generate_fee_to_pay_description(
			self::__( 'Customer pays:' ),
			$fee_plan->getCustomerFeeVariable() / 100,
			$fee_plan->getCustomerFeeFixed() / 100,
			'<br>' . sprintf(
				self::__( '<u>Note</u>: Customer fees are impacted by the usury rate, and will be adapted based on the limitations to comply with regulations. For more information, visit the Configuration page on your <a href="%s" target="_blank">Alma Dashboard</a>.' ),
				AssetsHelper::get_alma_dashboard_url( $environment, 'conditions' )
			)
		);
		$customer_lending_pays = self::generate_fee_to_pay_description(
			self::__( 'Customer lending rate:' ),
			$fee_plan->getCustomerLendingRate() / 100,
			0
		);

		$description = sprintf(
			'<p>%s<br>%s %s %s %s</p>',
			$you_can_offer,
			$fees_applied,
			$you_pay,
			$customer_pays,
			$customer_lending_pays
		);

		return array(
			'title'        => $section_title,
			'toggle_label' => $toggle_label,
			'description'  => $description,
		);
	}

	/**
	 * Generate a description for the fees to pay.
	 *
	 * @param string $translation The translation string to use.
	 * @param float  $fee_variable The variable fee percentage.
	 * @param float  $fee_fixed The fixed fee amount.
	 * @param string $fee_description An optional description for the fee, defaults to an empty string.
	 *
	 * @return string
	 */
	private static function generate_fee_to_pay_description( string $translation, float $fee_variable, float $fee_fixed, string $fee_description = '' ): string {
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
}
