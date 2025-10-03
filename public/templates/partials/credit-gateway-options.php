<?php
/**
 * @see Infrastructure/Gateway/Frontend/CreditGateway.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;

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
		/** @var FeePlanListAdapter $alma_woocommerce_gateway_fee_plan_list_adapter */
		/** @var FeePlanAdapter $alma_woocommerce_gateway_fee_plan_adapter */
		$alma_woocommerce_gateway_checked = 'checked';
		foreach ( $alma_woocommerce_gateway_fee_plan_list_adapter as $alma_woocommerce_gateway_fee_plan_adapter ) {
			if ( $alma_woocommerce_gateway_fee_plan_adapter->isEnabled() ) {
				$alma_plan_key_value = sprintf(
					'value="general_%d_%d_%d"',
					$alma_woocommerce_gateway_fee_plan_adapter->getInstallmentsCount(),
					$alma_woocommerce_gateway_fee_plan_adapter->getDeferredDays(),
					$alma_woocommerce_gateway_fee_plan_adapter->getDeferredMonths()
				);
				echo '<label>';
				echo '<input type="radio" name="alma_plan_key" ' . $alma_plan_key_value . ' ' . $alma_woocommerce_gateway_checked . ' />';
				echo esc_html(
					sprintf(
					// Translators: %d is the number of installments.
						L10nHelper::__( 'Paiement en %d fois' ),
						$alma_woocommerce_gateway_fee_plan_adapter->getInstallmentsCount()
					)
				);
				echo '</label><br>';
				$alma_woocommerce_gateway_checked = '';
			}
		}
		?>
	</p>
	<?php
	/** @var string $alma_woocommerce_gateway_nonce */
	echo $alma_woocommerce_gateway_nonce;
	?>
</fieldset>

<div id="alma-in-page-credit"></div>
