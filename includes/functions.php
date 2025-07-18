<?php

use Alma\API\Entities\Eligibility;
use Alma\API\Entities\FeePlan;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Helper\DisplayHelper;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\Business\Service\OptionsService;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;

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
		/** @var OptionsService $option_service */
		$option_service = Plugin::get_container()->get( OptionsService::class );

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

		$options = $option_service->get_options();
		ksort( $options );
		foreach ( $options as $key => $value ) {
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
		$fee_plan_list    = $fee_plan_service->get_fee_plan_list();

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
