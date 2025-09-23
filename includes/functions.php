<?php

use Alma\API\Domain\Entity\Eligibility;
use Alma\API\Domain\Entity\FeePlan;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Gateway\Plugin;

add_action(
	'wp_footer',
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
	function () {

		echo '<h1>All Options</h1>';
		echo '<pre>';
		/** @var ConfigService $configService */
		$configService = Plugin::get_container()->get( ConfigService::class );

		/*
				$configService->delete_option( 'general_10_0_0' );
				$configService->delete_option( 'general_10_0_0_description' );
				$configService->delete_option( 'general_10_0_0_enabled' );
				$configService->delete_option( 'general_10_0_0_max_amount' );
				$configService->delete_option( 'general_10_0_0_min_amount' );
				$configService->delete_option( 'general_10_0_0_title' );
				$configService->delete_option( 'general_12_0_0' );
				$configService->delete_option( 'general_12_0_0_description' );
				$configService->delete_option( 'general_12_0_0_enabled' );
				$configService->delete_option( 'general_12_0_0_max_amount' );
				$configService->delete_option( 'general_12_0_0_min_amount' );
				$configService->delete_option( 'general_12_0_0_title' );
				$configService->delete_option( 'general_1_0_0' );
				$configService->delete_option( 'general_1_0_0_description' );
				$configService->delete_option( 'general_1_0_0_enabled' );
				$configService->delete_option( 'general_1_0_0_max_amount' );
				$configService->delete_option( 'general_1_0_0_min_amount' );
				$configService->delete_option( 'general_1_0_0_title' );
				$configService->delete_option( 'general_1_15_0' );
				$configService->delete_option( 'general_1_15_0_description' );
				$configService->delete_option( 'general_1_15_0_enabled' );
				$configService->delete_option( 'general_1_15_0_max_amount' );
				$configService->delete_option( 'general_1_15_0_min_amount' );
				$configService->delete_option( 'general_1_15_0_title' );
				$configService->delete_option( 'general_1_30_0' );
				$configService->delete_option( 'general_1_30_0_description' );
				$configService->delete_option( 'general_1_30_0_enabled' );
				$configService->delete_option( 'general_1_30_0_max_amount' );
				$configService->delete_option( 'general_1_30_0_min_amount' );
				$configService->delete_option( 'general_1_30_0_title' );
				$configService->delete_option( 'general_2_0_0' );
				$configService->delete_option( 'general_2_0_0_description' );
				$configService->delete_option( 'general_2_0_0_enabled' );
				$configService->delete_option( 'general_2_0_0_max_amount' );
				$configService->delete_option( 'general_2_0_0_min_amount' );
				$configService->delete_option( 'general_2_0_0_title' );
				$configService->delete_option( 'general_3_0_0' );
				$configService->delete_option( 'general_3_0_0_description' );
				$configService->delete_option( 'general_3_0_0_enabled' );
				$configService->delete_option( 'general_3_0_0_max_amount' );
				$configService->delete_option( 'general_3_0_0_min_amount' );
				$configService->delete_option( 'general_3_0_0_title' );
				$configService->delete_option( 'general_4_0_0' );
				$configService->delete_option( 'general_4_0_0_description' );
				$configService->delete_option( 'general_4_0_0_enabled' );
				$configService->delete_option( 'general_4_0_0_max_amount' );
				$configService->delete_option( 'general_4_0_0_min_amount' );
				$configService->delete_option( 'general_4_0_0_title' );
		*/

		$options = $configService->getSettings();
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
	function () {
		if ( ! is_checkout() ) {
			return;
		}

		echo '<h1>Fee plans from API</h1>';
		echo '<pre>';
		try {
			/** @var FeePlanRepository $feePlanRepository */
			$feePlanRepository  = Plugin::get_container()->get( FeePlanRepository::class );
			$feePlanListAdapter = $feePlanRepository->getAll();
		} catch ( FeePlanServiceException $e ) {
			echo 'Error: ' . $e->getMessage();
			die;
		}

		/** @var FeePlan $fee_plan */
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {
			echo '<h2>' . $feePlanAdapter->getPlanKey() . '</h2>'
				. '<ul>'
				. '<li>Min amount: ' . $feePlanAdapter->getMinPurchaseAmount() . '</li>'
				. '<li>Max amount: ' . $feePlanAdapter->getMaxPurchaseAmount() . '</li>'
				. '<li>Override Min amount: ' . $feePlanAdapter->getOverrideMinPurchaseAmount() . '</li>'
				. '<li>Override Max amount: ' . $feePlanAdapter->getOverrideMaxPurchaseAmount() . '</li>'
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
