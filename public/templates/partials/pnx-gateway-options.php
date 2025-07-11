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
				echo '<label>';
				echo '<input type="radio" name="alma_installments" value="' . $alma_woocommerce_gateway_fee_plan->getInstallmentsCount() . '" />';
				echo esc_html(
					sprintf(
					// Translators: %d is the number of installments.
						__( 'Paiement en %d fois', 'alma-gateway-for-woocommerce' ),
						$alma_woocommerce_gateway_fee_plan->getInstallmentsCount()
					)
				);
				echo '<label><br>';
			}
		}
		?>
	</p>
	<?php WordPressProxy::set_nonce( 'alma_pnx_gateway_nonce_action', 'alma_pnx_gateway_nonce_field' ); ?>
</fieldset>
