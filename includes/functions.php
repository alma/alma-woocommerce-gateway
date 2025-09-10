<?php

use Alma\API\Entity\Eligibility;
use Alma\API\Entity\FeePlan;
use Alma\API\Domain\Exception\ContainerException;
use Alma\API\Domain\Exception\MerchantServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\LoggerService;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Plugin;

add_action(
	'wp_footer',
	/** @throws ContainerException */
	function () {
		if ( ! is_checkout() ) {
			return;
		}

		echo '<h1>All Payment Gateways</h1>';
		echo '<pre>';
		/** @var AbstractGateway $gateway */
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			$availability = $gateway->is_available() ? 'available' : 'not available';
			echo $gateway->id . ' (Enabled: => ' . $gateway->enabled . ' / Available: => ' . $availability . ")\n";
			if ( $gateway instanceof AbstractGateway ) {
				/** @var Eligibility $eligibility */
				foreach ( $gateway->eligibility_list as $eligibility ) {
					echo '  - ' . $eligibility->getPlanKey() . ' => ' . ( $eligibility->isEligible() ? 'eligible' : 'not eligible' )
						. "\n";
				}
			} else {
				echo ' - Not an Alma gateway' . "\n";
			}
		}
		echo '</pre>';
	}
);

add_action(
	'wp_footer',
	/** @throws ContainerException */
	function () {

		echo '<h1>All Options</h1>';
		echo '<pre>';
		/** @var ConfigService $option_service */
		$option_service = Plugin::get_container()->get( ConfigService::class );

		/*
				$option_service->delete_option( 'general_10_0_0' );
				$option_service->delete_option( 'general_10_0_0_description' );
				$option_service->delete_option( 'general_10_0_0_enabled' );
				$option_service->delete_option( 'general_10_0_0_max_amount' );
				$option_service->delete_option( 'general_10_0_0_min_amount' );
				$option_service->delete_option( 'general_10_0_0_title' );
				$option_service->delete_option( 'general_12_0_0' );
				$option_service->delete_option( 'general_12_0_0_description' );
				$option_service->delete_option( 'general_12_0_0_enabled' );
				$option_service->delete_option( 'general_12_0_0_max_amount' );
				$option_service->delete_option( 'general_12_0_0_min_amount' );
				$option_service->delete_option( 'general_12_0_0_title' );
				$option_service->delete_option( 'general_1_0_0' );
				$option_service->delete_option( 'general_1_0_0_description' );
				$option_service->delete_option( 'general_1_0_0_enabled' );
				$option_service->delete_option( 'general_1_0_0_max_amount' );
				$option_service->delete_option( 'general_1_0_0_min_amount' );
				$option_service->delete_option( 'general_1_0_0_title' );
				$option_service->delete_option( 'general_1_15_0' );
				$option_service->delete_option( 'general_1_15_0_description' );
				$option_service->delete_option( 'general_1_15_0_enabled' );
				$option_service->delete_option( 'general_1_15_0_max_amount' );
				$option_service->delete_option( 'general_1_15_0_min_amount' );
				$option_service->delete_option( 'general_1_15_0_title' );
				$option_service->delete_option( 'general_1_30_0' );
				$option_service->delete_option( 'general_1_30_0_description' );
				$option_service->delete_option( 'general_1_30_0_enabled' );
				$option_service->delete_option( 'general_1_30_0_max_amount' );
				$option_service->delete_option( 'general_1_30_0_min_amount' );
				$option_service->delete_option( 'general_1_30_0_title' );
				$option_service->delete_option( 'general_2_0_0' );
				$option_service->delete_option( 'general_2_0_0_description' );
				$option_service->delete_option( 'general_2_0_0_enabled' );
				$option_service->delete_option( 'general_2_0_0_max_amount' );
				$option_service->delete_option( 'general_2_0_0_min_amount' );
				$option_service->delete_option( 'general_2_0_0_title' );
				$option_service->delete_option( 'general_3_0_0' );
				$option_service->delete_option( 'general_3_0_0_description' );
				$option_service->delete_option( 'general_3_0_0_enabled' );
				$option_service->delete_option( 'general_3_0_0_max_amount' );
				$option_service->delete_option( 'general_3_0_0_min_amount' );
				$option_service->delete_option( 'general_3_0_0_title' );
				$option_service->delete_option( 'general_4_0_0' );
				$option_service->delete_option( 'general_4_0_0_description' );
				$option_service->delete_option( 'general_4_0_0_enabled' );
				$option_service->delete_option( 'general_4_0_0_max_amount' );
				$option_service->delete_option( 'general_4_0_0_min_amount' );
				$option_service->delete_option( 'general_4_0_0_title' );
		*/

		$options = $option_service->getSettings();
		ksort( $options );
		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = json_encode( $value );
			} elseif ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			echo $key . ' => ' . $value . "\n";
		}
		echo '</pre>';
	}
);

add_action(
	'wp_footer',
	/** @throws ContainerException
	 * @throws MerchantServiceException
	 */
	function () {
		if ( ! is_checkout() ) {
			return;
		}

		echo '<h1>Fee plans from API</h1>';
		echo '<pre>';
		/** @var FeePlanService $fee_plan_service */
		$fee_plan_service = Plugin::get_container()->get( FeePlanService::class );
		$fee_plan_list    = $fee_plan_service->getFeePlanList();

		/** @var FeePlan $fee_plan */
		foreach ( $fee_plan_list as $fee_plan ) {
			echo '<h2>' . $fee_plan->getPlanKey() . '</h2>'
				. '<ul>'
				. '<li>Min amount: ' . DisplayHelper::price_to_euro( $fee_plan->getMinPurchaseAmount() ) . '</li>'
				. '<li>Max amount: ' . DisplayHelper::price_to_euro( $fee_plan->getMaxPurchaseAmount() ) . '</li>'
				. '<li>Override Min amount: ' . DisplayHelper::price_to_euro( $fee_plan->getMinPurchaseAmount( true ) ) . '</li>'
				. '<li>Override Max amount: ' . DisplayHelper::price_to_euro( $fee_plan->getMaxPurchaseAmount( true ) ) . '</li>'
				. '</ul>';
		}

		echo '</pre>';
	}
);

function almalog( $message, $data = null ) {
	/** @var LoggerService $logger */
	$logger = Plugin::get_container()->get( LoggerService::class );
	$logger->debug( $message );
	if ( ! is_null( $data ) ) {
		$logger->debug( wp_json_encode( $data ) );
	}
}
