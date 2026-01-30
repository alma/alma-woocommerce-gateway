<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Client\Domain\ValueObject\Environment;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\LanguageHelper;
use NumberFormatter;

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
	 * It's a convention to use __() for translations
	 * @phpcs We pass a variable to __() call because it's a proxy!
	 */
	public static function __( string $translation, string $domain = self::ALMA_L10N_DOMAIN ): string {
		return LanguageHelper::__( $translation, $domain );// phpcs:ignore
	}

	/**
	 * Load the plugin language files.
	 *
	 * @param $language_path
	 *
	 * @return void
	 */
	public static function load_language( $language_path ) {
		LanguageHelper::loadLanguage( self::ALMA_L10N_DOMAIN, $language_path );
	}

	/**
	 * Format currency from cents to localized string.
	 *
	 * @param int $amountInCents
	 *
	 * @return string
	 */
	public static function format_currency( int $amountInCents ): string {
		$formatter = new NumberFormatter( ContextHelper::getLocale(), NumberFormatter::CURRENCY );

		return $formatter->formatCurrency( $amountInCents / 100, 'EUR' );
	}

	/**
	 * Generate a title and a toggle description for the fee plan.
	 *
	 * @param FeePlanAdapter $fee_plan_adapter
	 * @param Environment    $environment
	 *
	 * @return array
	 * @todo should we move this to FeePlanAdapter Object?
	 */
	public static function generate_fee_plan_display_data( FeePlanAdapter $fee_plan_adapter, Environment $environment ): array {
		$installments    = $fee_plan_adapter->getInstallmentsCount();
		$deferred_days   = $fee_plan_adapter->getDeferredDays();
		$deferred_months = $fee_plan_adapter->getDeferredMonths();
		$min             = DisplayHelper::price_to_euro( $fee_plan_adapter->getOverrideMinPurchaseAmount() );
		$max             = DisplayHelper::price_to_euro( $fee_plan_adapter->getOverrideMaxPurchaseAmount() );

		$section_title = '';
		$toggle_label  = '';
		$description   = '';

		if ( $fee_plan_adapter->isPayNow() ) {
			$section_title = __( '→ Pay Now' );
			$toggle_label  = sprintf(
				__( 'Enable %d-installment payments with Alma' ),
				$installments
			);
			$description   = sprintf(
				__( 'You can offer instant payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
				$installments,
				$min,
				$max
			);
		} elseif ( $fee_plan_adapter->isPnXOnly() || $fee_plan_adapter->isCredit() ) {
			$section_title = sprintf(
				__( '→ %d-installment payment' ),
				$installments
			);
			$toggle_label  = sprintf(
				__( 'Enable %d-installment payments with Alma' ),
				$installments
			);
			$description   = sprintf(
				__( 'You can offer %1$d-installment payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
				$installments,
				$min,
				$max
			);
		} elseif ( $fee_plan_adapter->isPayLaterOnly() ) {
			if ( $deferred_days > 0 ) {
				$section_title = sprintf( __( '→ D+%d-deferred payment' ), $deferred_days );
				$toggle_label  = sprintf( __( 'Enable D+%d-deferred payments with Alma' ), $deferred_days );
				$description   = sprintf(
					__( 'You can offer D+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
					$deferred_days,
					$min,
					$max
				);
			} elseif ( $deferred_months > 0 ) {
				$section_title = sprintf( __( '→ M+%d-deferred payment' ), $deferred_months );
				$toggle_label  = sprintf(
					__( 'Enable M+%d-deferred payments with Alma' ),
					$deferred_months
				);
				$description   = sprintf(
					__( 'You can offer M+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
					$deferred_months,
					$min,
					$max
				);
			}
		}

		return array(
			'title'        => $section_title,
			'toggle_label' => $toggle_label,
			'description'  => $description,
		);
	}
}
