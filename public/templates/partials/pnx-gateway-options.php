<?php
/**
 * @see Infrastructure/Gateway/Frontend/PnxGateway.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;

?>

<div class="alma_woocommerce_gateway_fieldset alma_woocommerce_gateway_pnx">
	<p>
		<?php
		esc_html_e(
			'Choisissez en combien de fois vous souhaitez payer avec Alma.',
			'alma-gateway-for-woocommerce'
		);
		?>
		<br/>
		<br/>
	</p>
	<p>
		<?php
		/** @var FeePlanListAdapter $alma_woocommerce_gateway_fee_plan_list_adapter */
		/** @var FeePlanAdapter $alma_woocommerce_gateway_fee_plan_adapter */
		foreach ( $alma_woocommerce_gateway_fee_plan_list_adapter as $alma_woocommerce_gateway_fee_plan_adapter ) {
			if ( $alma_woocommerce_gateway_fee_plan_adapter->isEnabled() ) {
				$alma_plan_key_value = sprintf(
					'value="general_%d_%d_%d"',
					$alma_woocommerce_gateway_fee_plan_adapter->getInstallmentsCount(),
					$alma_woocommerce_gateway_fee_plan_adapter->getDeferredDays(),
					$alma_woocommerce_gateway_fee_plan_adapter->getDeferredMonths()
				);
				echo '<label>';
				echo '<input type="radio" name="alma_plan_key" ' . $alma_plan_key_value . ' />';
				echo esc_html(
					sprintf(
					// Translators: %d is the number of installments.
						L10nHelper::__( '&nbsp;Paiement en %d fois' ),
						$alma_woocommerce_gateway_fee_plan_adapter->getInstallmentsCount()
					)
				);
				echo '</label><br>';
			}
		}
		?>
	</p>
	<?php
	/** @var string $alma_woocommerce_gateway_nonce */
	echo $alma_woocommerce_gateway_nonce;
	?>
</div>

<?php /** @var string $alma_woocommerce_gateway_in_page_iframe_selector */ ?>
<div id="<?php echo $alma_woocommerce_gateway_in_page_iframe_selector; ?>"></div>
