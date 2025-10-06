<?php
/**
 * @see Infrastructure/Gateway/Frontend/PayLaterGateway.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;

?>

<fieldset class="alma_woocommerce_gateway_fieldset alma_woocommerce_gateway_pay-later">
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
				if ( $alma_woocommerce_gateway_fee_plan_adapter->getDeferredDays() > 0 ) {
					echo esc_html(
						sprintf(
						// Translators: %d is the number of deferred days
							L10nHelper::__( 'Achetez maintenant, payez dans %d jours' ),
							$alma_woocommerce_gateway_fee_plan_adapter->getDeferredDays()
						)
					);
				}
				if ( $alma_woocommerce_gateway_fee_plan_adapter->getDeferredMonths() > 0 ) {
					echo esc_html(
						sprintf(
						// Translators: %d is the number of deferred months
							L10nHelper::__( 'Achetez maintenant, payez dans %d mois' ),
							$alma_woocommerce_gateway_fee_plan_adapter->getDeferredMonths()
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

<?php /** @var string $alma_woocommerce_gateway_in_page_iframe_selector */ ?>
<div id="<?php echo $alma_woocommerce_gateway_in_page_iframe_selector; ?>"></div>