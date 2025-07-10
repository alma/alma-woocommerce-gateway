<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\API\Entities\FeePlan;
use Alma\API\Entities\FeePlanList;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

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
				$alma_woocommerce_gateway_value = sprintf(
					'%_%',
					$alma_woocommerce_gateway_fee_plan->getDeferredDays(),
					$alma_woocommerce_gateway_fee_plan->getDeferredMonths()
				);
				echo '<label>';
				echo '<input type="radio" name="alma_deferred" value="' . $alma_woocommerce_gateway_value . '" />';
				if ( $alma_woocommerce_gateway_fee_plan->getDeferredDays() > 0 ) {
					echo esc_html(
						sprintf(
						// Translators: %d is the number of deferred days
							__( 'Achetez maintenant, payez dans %s jours', 'alma-gateway-for-woocommerce' ),
							$alma_woocommerce_gateway_fee_plan->getDeferredDays()
						)
					);
				}
				if ( $alma_woocommerce_gateway_fee_plan->getDeferredMonths() > 0 ) {
					echo esc_html(
						sprintf(
						// Translators: %d is the number of deferred months
							__( 'Achetez maintenant, payez dans %s mois', 'alma-gateway-for-woocommerce' ),
							$alma_woocommerce_gateway_fee_plan->getDeferredMonths()
						)
					);
				}
				echo '<label><br>';
			}
		}
		?>
	</p>
	<?php WordPressProxy::set_nonce( 'alma_pnx_gateway_nonce_action', 'alma_pnx_gateway_nonce_field' ); ?>
</fieldset>
