<?php
/**
 * Template.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 */


use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Models\Alma_Cart;

?>

<div id="alma-checkout-plan-details">
	<?php
foreach ($eligibilities as $key => $eligibility ) {
	?>
	<div
		id="<?php echo esc_attr( sprintf( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $key ) ); ?>"
		class="<?php echo esc_attr( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS ); ?>"
		data-gateway-id="<?php echo esc_attr( $gateway_id); ?>"
		style="
			margin: 0 auto;
		<?php if ( $key !== $default_plan ) { ?>
			display: none;
		<?php } ?>
			"
	>
		<?php
		$plan_index   = 1;
		$payment_plan = $eligibility->paymentPlan; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		$plans_count = count( $payment_plan );
		foreach ( $payment_plan as $step ) {
			$display_customer_fee = 1 === $plan_index && $eligibility->getInstallmentsCount() <= 4 && $step['customer_fee'] > 0;
			?>
			<!--suppress CssReplaceWithShorthandSafely -->
			<p style="
				padding: 4px 0;
				margin: 4px 0;
			<?php if ( ! $eligibility->isPayLaterOnly() ) { ?>
				display: flex;
				justify-content: space-between;
			<?php } ?>
			<?php if ( $plan_index === $plans_count || $display_customer_fee ) { ?>
				padding-bottom: 0;
				margin-bottom: 0;
			<?php } else { ?>
				border-bottom: 1px solid lightgrey;
			<?php } ?>
				">
				<?php
				if ( $eligibility->isPayLaterOnly() ) {
					$justify_fees = 'left';
					echo wp_kses_post(
						sprintf(
						// translators: %1$s => today_amount (0), %2$s => total_amount, %3$s => i18n formatted due_date.
							__( '%1$s today then %2$s on %3$s', 'alma-gateway-for-woocommerce' ),
							Alma_Tools_Helper::alma_format_price_from_cents( 0 ),
							Alma_Tools_Helper::alma_format_price_from_cents( $step['total_amount'] ),
							date_i18n( get_option( 'date_format' ), $step['due_date'] )
						)
					);                      } else {
					$justify_fees = 'right';
					if ( 'yes' === $this->alma_settings->payment_upon_trigger_enabled && $eligibility->getInstallmentsCount() <= 4 ) {
						echo '<span>' . esc_html( $this->get_plan_upon_trigger_display_text( $plan_index ) ) . '</span>';
					} else {
						echo '<span>' . esc_html( date_i18n( get_option( 'date_format' ), $step['due_date'] ) ) . '</span>';
					}
					echo wp_kses_post( Alma_Tools_Helper::alma_format_price_from_cents( $step['total_amount'] ) );                      }
				?>
			</p>
			<?php if ( $display_customer_fee ) { ?>
				<p style="
					display: flex;
					justify-content: <?php echo esc_attr( $justify_fees ); ?>;
					padding: 0 0 4px 0;
					margin: 0 0 4px 0;
					border-bottom: 1px solid lightgrey;
					">
					<span><?php echo esc_html__( 'Included fees:', 'alma-gateway-for-woocommerce' ); ?><?php echo wp_kses_post( Alma_Tools_Helper::alma_format_price_from_cents( $step['customer_fee'] ) ); ?></span>
				</p>
				<?php
			}
			$plan_index++;
		} // end foreach

		if ( $eligibility->getInstallmentsCount() > 4 ) {
			$cart = new Alma_Cart();
			?>
			<p style="
			display: flex;
			justify-content: left;
			padding: 20px 0 4px 0;
			margin: 4px 0;
			font-size: 1.8rem;
			font-weight: bold;
			border-top: 1px solid lightgrey;
		">
				<span><?php echo esc_html__( 'Your credit', 'alma-gateway-for-woocommerce' ); ?></span>
			</p>
			<p style="
			display: flex;
			justify-content: space-between;
			padding: 4px 0;
			margin: 4px 0;
			border-bottom: 1px solid lightgrey;
		">
				<span><?php echo esc_html__( 'Your cart:', 'alma-gateway-for-woocommerce' ); ?></span>
				<span><?php echo wp_kses_post( Alma_Tools_Helper::alma_format_price_from_cents( $cart->get_total_in_cents() ) ); ?></span>
			</p>
			<p style="
			display: flex;
			justify-content: space-between;
			padding: 4px 0;
			margin: 4px 0;
			border-bottom: 1px solid lightgrey;
		">
				<span><?php echo esc_html__( 'Credit cost:', 'alma-gateway-for-woocommerce' ); ?></span>
				<span><?php echo wp_kses_post( Alma_Tools_Helper::alma_format_price_from_cents( $eligibility->customerTotalCostAmount ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
			</p>
			<?php
			$annual_interest_rate = $eligibility->getAnnualInterestRate();
			if ( ! is_null( $annual_interest_rate ) && $annual_interest_rate > 0 ) {
				?>
				<p style="
			display: flex;
				justify-content: space-between;
				padding: 4px 0;
				margin: 4px 0;
				border-bottom: 1px solid lightgrey;
			">
					<span><?php echo esc_html__( 'Annual Interest Rate:', 'alma-gateway-for-woocommerce' ); ?></span>
					<span><?php echo wp_kses_post( Alma_Tools_Helper::alma_format_percent_from_bps( $annual_interest_rate ) ); ?></span>
				</p>
			<?php } ?>
			<p style="
			display: flex;
			justify-content: space-between;
			padding: 4px 0 0 0;
			margin: 4px 0 0 0;
			font-weight: bold;
		">
				<span><?php echo esc_html__( 'Total:', 'alma-gateway-for-woocommerce' ); ?></span>
				<span><?php echo wp_kses_post( Alma_Tools_Helper::alma_format_price_from_cents( $eligibility->getCustomerTotalCostAmount() + $cart->get_total_in_cents() ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
			</p>
			<?php
		}
		?>
	</div>
	<?php
}
?>
</div>
