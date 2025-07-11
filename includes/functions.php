<?php

use Alma\API\Entities\Eligibility;
use Alma\API\Entities\FeePlan;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
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
				echo '  - Not an Alma gateway' . "\n";
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

		$option_service->delete_option( '' );

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
				. '<li>Min amount: ' . $fee_plan->getMinPurchaseAmount() / 100 . '</li>'
				. '<li>Max amount: ' . $fee_plan->getMaxPurchaseAmount() / 100 . '</li>'
				. '<li>Override Min amount: ' . $fee_plan->getMinPurchaseAmount( true ) / 100 . '</li>'
				. '<li>Override Max amount: ' . $fee_plan->getMaxPurchaseAmount( true ) / 100 . '</li>'
				. '</ul>';
		}

		echo '</pre>';
	}
);
