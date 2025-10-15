<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\API\Entity\FeePlan;
use Alma\API\Entity\FeePlanList;
use Alma\Gateway\Application\Helper\L10nHelper;

?>

<fieldset>
	<p>
		<?php
		esc_html_e(
			'Choisissez quand vous souhaitez payer avec Alma.',
			'alma-gateway-for-woocommerce'
		);
		?>
	</p>
	<p>
		<?php
		/** @var FeePlanList $alma_woocommerce_gateway_fee_plan_list */
		/** @var FeePlan $alma_woocommerce_gateway_fee_plan */
		foreach ( $alma_woocommerce_gateway_fee_plan_list as $alma_woocommerce_gateway_fee_plan ) {
			if ( $alma_woocommerce_gateway_fee_plan->isEnabled() ) {
				$alma_plan_key_value = sprintf(
					'value="general_%d_%d_%d"',
					$alma_woocommerce_gateway_fee_plan->getInstallmentsCount(),
					$alma_woocommerce_gateway_fee_plan->getDeferredDays(),
					$alma_woocommerce_gateway_fee_plan->getDeferredMonths()
				);
				echo '<label>';
				echo '<input type="radio" name="alma_plan_key" ' . $alma_plan_key_value . ' />';
				if ( $alma_woocommerce_gateway_fee_plan->getDeferredDays() > 0 ) {
					echo esc_html(
						sprintf(
						// Translators: %d is the number of deferred days
							L10nHelper::__( 'Achetez maintenant, payez dans %d jours' ),
							$alma_woocommerce_gateway_fee_plan->getDeferredDays()
						)
					);
				}
				if ( $alma_woocommerce_gateway_fee_plan->getDeferredMonths() > 0 ) {
					echo esc_html(
						sprintf(
						// Translators: %d is the number of deferred months
							L10nHelper::__( 'Achetez maintenant, payez dans %d mois' ),
							$alma_woocommerce_gateway_fee_plan->getDeferredMonths()
						)
					);
				}
				echo '</label><br>';
			}
		}
		?>
	</p>
	<?php
	/** @var string $alma_woocommerce_gateway_nonce */
	echo $alma_woocommerce_gateway_nonce;
	?>
</fieldset>
