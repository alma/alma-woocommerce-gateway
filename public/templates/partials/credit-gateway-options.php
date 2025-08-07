<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\API\Entity\FeePlan;
use Alma\API\Entity\FeePlanList;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WordPressProxy;

?>

<fieldset>
	<p>
		<?php
		esc_html_e(
			'Choisissez en combien de fois vous souhaitez payer avec Alma.',
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
				echo esc_html(
					sprintf(
					// Translators: %d is the number of installments.
						L10nHelper::__( 'Paiement en %d fois' ),
						$alma_woocommerce_gateway_fee_plan->getInstallmentsCount()
					)
				);
				echo '</label><br>';
			}
		}
		?>
	</p>
	<?php WordPressProxy::set_nonce( 'alma_credit_gateway_nonce_action', 'alma_credit_gateway_nonce_field' ); ?>
</fieldset>
