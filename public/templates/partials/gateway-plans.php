<?php
/**
 * Template.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 */

/**
 * @var array          $args Template arguments
 * @var string         $args ['alma_woocommerce_gateway_plan_key'] Plan key
 * @var string         $args ['alma_woocommerce_gateway_name'] Gateway name
 * @var FeePlanAdapter $args ['alma_woocommerce_gateway_fee_plan'] Fee plan
 * @var bool           $args ['alma_woocommerce_gateway_in_page_enabled'] In-page enabled
 * @var string         $args ['alma_woocommerce_gateway_in_page_iframe_selector'] Iframe selector
 * @var string         $args ['alma_woocommerce_gateway_nonce'] Nonce
 */

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}
?>
<div
	id="alma-checkout-plan-<?php echo esc_attr( $args['alma_woocommerce_gateway_plan_key'] ); ?>"
	class="alma_woocommerce_gateway_checkout_plan"
	data-gateway-id="<?php echo esc_attr( $args['alma_woocommerce_gateway_name'] ); ?>"
	style="display: none"
>
	<?php
	if ( $args['alma_woocommerce_gateway_in_page_enabled'] ) {
		?>
		<div id="<?php echo $args['alma_woocommerce_gateway_in_page_iframe_selector']; ?>"></div>
		<?php
	} else {
		?>
		<table class="alma_woocommerce_gateway_installments">
			<?php
			$alma_plan_index = 1;

			foreach ( $args['alma_woocommerce_gateway_fee_plan']->getPaymentPlan() as $alma_woocommerce_gateway_installment ) {
				?>
				<tr class="alma_woocommerce_gateway_installment">
					<?php
					if ( $args['alma_woocommerce_gateway_fee_plan']->isPayLaterOnly() ) {
						printf(
						// translators: %1$s => today_amount (0), %2$s => total_amount, %3$s => i18n formatted due_date.
							__( '%1$s today then %2$s on %3$s', 'alma-gateway-for-woocommerce' ),
							L10nHelper::format_currency( 0 ),
							L10nHelper::format_currency( $alma_woocommerce_gateway_installment['total_amount'] ),
							date_i18n( get_option( 'date_format' ), $alma_woocommerce_gateway_installment['due_date'] )
						);
					} else {
						echo '<td class="alma_woocommerce_gateway_due_date">' . esc_html(
							date_i18n(
								get_option( 'date_format' ),
								$alma_woocommerce_gateway_installment['due_date']
							)
						) . '</td>';
						echo '<td class="alma_woocommerce_gateway_amount">' . L10nHelper::format_currency( $alma_woocommerce_gateway_installment['total_amount'] ) . '</td>';
					}
					?>
				</tr>
				<?php if ( 1 === $alma_plan_index && $args['alma_woocommerce_gateway_fee_plan']->getCustomerFee() ) { ?>
					<tr>
						<td>
							<?php
							echo esc_html__(
								'Included fees:',
								'alma-gateway-for-woocommerce'
							);
							?>
						</td>
						<td class="alma_woocommerce_gateway_amount">
							<?php echo L10nHelper::format_currency( $args['alma_woocommerce_gateway_fee_plan']->getCustomerFee() ); ?>
					</tr>
					<?php

				}
				++$alma_plan_index;
			} // end foreach
			?>
		</table>
		<?php
	}
	?>
	<?php
	echo $args['alma_woocommerce_gateway_nonce'];
	?>
</div>
