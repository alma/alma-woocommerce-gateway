<?php
/**
 * Template.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 * @var string         $alma_woocommerce_gateway_plan_key
 * @var string         $alma_woocommerce_gateway_name
 * @var FeePlanAdapter $alma_woocommerce_gateway_fee_plan
 */

use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;

?>
<div
	id="alma-checkout-plan-<?php echo esc_attr( $alma_woocommerce_gateway_plan_key ); ?>"
	class="alma_woocommerce_gateway_checkout_plan"
	data-gateway-id="<?php echo esc_attr( $alma_woocommerce_gateway_name ); ?>"
	style="display: none;"
>
	<table class="alma_woocommerce_gateway_installments">
		<?php
		$alma_plan_index = 1;

		foreach ( $alma_woocommerce_gateway_fee_plan->getPaymentPlan() as $alma_woocommerce_gateway_installment ) {
			?>
			<tr>
				<?php
				if ( $alma_woocommerce_gateway_fee_plan->isPayLaterOnly() ) {
					printf(
					// translators: %1$s => today_amount (0), %2$s => total_amount, %3$s => i18n formatted due_date.
						__( '%1$s today then %2$s on %3$s', 'alma-gateway-for-woocommerce' ),
						0,
						$alma_woocommerce_gateway_installment['total_amount'],
						date_i18n( get_option( 'date_format' ), $alma_woocommerce_gateway_installment['due_date'] )
					);
				} else {
					echo '<td class="alma_woocommerce_gateway_installment alma_woocommerce_gateway_due_date">' . esc_html(
						date_i18n(
							get_option( 'date_format' ),
							$alma_woocommerce_gateway_installment['due_date']
						)
					) . '</td>';
					echo '<td class="alma_woocommerce_gateway_installment alma_woocommerce_gateway_amount">' . $alma_woocommerce_gateway_installment['total_amount'] . '</td>';
				}
				?>
				<?php if ( $alma_woocommerce_gateway_fee_plan->getCustomerFee() ) { ?>
					<td colspan="2" class="alma_woocommerce_gateway_fees">
						<?php
						echo esc_html__(
							'Included fees:',
							'alma-gateway-for-woocommerce'
						);
						?>

						<?php echo $alma_woocommerce_gateway_fee_plan->getCustomerFee(); ?>
					</td>
					<?php
				}
				?>
			</tr>
			<?php
		} // end foreach

		if ( $alma_woocommerce_gateway_fee_plan->isCredit() ) {
			?>
			<tr class="alma_woocommerce_gateway_credit_title">
				<th colspan="2"><?php echo esc_html__( 'Your credit', 'alma-gateway-for-woocommerce' ); ?></th>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Your cart:', 'alma-gateway-for-woocommerce' ); ?></td>
				<td class="alma_woocommerce_gateway_credit alma_woocommerce_gateway_amount"><?php echo $alma_woocommerce_gateway_fee_plan->getCustomerTotalCostAmount(); ?></td>
			</tr>
			<tr>
				<td><?php echo esc_html__( 'Credit cost:', 'alma-gateway-for-woocommerce' ); ?></td>
				<td class="alma_woocommerce_gateway_credit alma_woocommerce_gateway_amount"><?php echo $alma_woocommerce_gateway_fee_plan->getCustomerTotalCostBps(); ?></td>
			</tr>
			<?php
			$alma_annual_interest_rate = $alma_woocommerce_gateway_fee_plan->getAnnualInterestRate();
			if ( ! is_null( $alma_annual_interest_rate ) && $alma_annual_interest_rate > 0 ) {
				?>
				<tr>
					<td>
						<?php
						echo esc_html__(
							'Annual Interest Rate:',
							'alma-gateway-for-woocommerce'
						);
						?>
					</td>
					<td class="alma_woocommerce_gateway_credit alma_woocommerce_gateway_amount"><?php echo $alma_annual_interest_rate; ?></td>
				</tr>
			<?php } ?>
			<tr class="alma_woocommerce_gateway_installments_total">
				<td><?php echo esc_html__( 'Total:', 'alma-gateway-for-woocommerce' ); ?></td>
				<td class="alma_woocommerce_gateway_credit alma_woocommerce_gateway_amount"><?php echo $alma_woocommerce_gateway_fee_plan->getCustomerTotalCostAmount(); ?></td>
			</tr>
			<?php
		}
		?>
	</table>
</div>
