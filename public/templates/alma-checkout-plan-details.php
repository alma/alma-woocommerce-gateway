<?php
/**
 * Template.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 */

use Alma\Woocommerce\Builders\Helpers\CartHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;

/**
 * The tools helper
 *
 * @var ToolsHelper $alma_tools_helper
 */
$alma_tools_helper_builder = new ToolsHelperBuilder();
$alma_tools_helper         = $alma_tools_helper_builder->get_instance();
?>

<div id="alma-checkout-plan-details">
	<?php
	foreach ( $alma_eligibilities as $alma_key => $alma_eligibility ) {
		?>
	<div
		id="<?php echo esc_attr( sprintf( ConstantsHelper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $alma_key ) ); ?>"
		class="<?php echo esc_attr( ConstantsHelper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS ); ?>"
		data-gateway-id="<?php echo esc_attr( $alma_gateway_id ); ?>"
		style="
			margin: 0 auto 15px auto;
		<?php if ( $alma_key !== $alma_default_plan ) { ?>
			display: none;
		<?php } ?>
			"
	>
		<?php
		$alma_plan_index = 1;

		$alma_payment_plan = $alma_eligibility->paymentPlan; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		if ( is_null( $alma_payment_plan ) ) {
			$alma_payment_plan = array();
		}

		if ( is_array( $alma_payment_plan ) ) {
			$alma_plans_count = count( $alma_payment_plan );
		}
		foreach ( $alma_payment_plan as $alma_step ) {
			$alma_display_customer_fee = 1 === $alma_plan_index && $alma_eligibility->getInstallmentsCount() <= 4 && $alma_step['customer_fee'] > 0;
			?>
			<!--suppress CssReplaceWithShorthandSafely -->
			<p style="
				padding: 4px 0;
				margin: 4px 0;
			<?php if ( ! $alma_eligibility->isPayLaterOnly() ) { ?>
				display: flex;
				justify-content: space-between;
			<?php } ?>
			<?php if ( $alma_plan_index === $alma_plans_count || $alma_display_customer_fee ) { ?>
				padding-bottom: 0;
				margin-bottom: 0;
			<?php } else { ?>
				border-bottom: 1px solid lightgrey;
			<?php } ?>
				">
				<?php
				if ( $alma_eligibility->isPayLaterOnly() ) {
					$alma_justify_fees = 'left';
					echo wp_kses_post(
						sprintf(
						// translators: %1$s => today_amount (0), %2$s => total_amount, %3$s => i18n formatted due_date.
							__( '%1$s today then %2$s on %3$s', 'alma-gateway-for-woocommerce' ),
							$alma_tools_helper->alma_format_price_from_cents( 0 ),
							$alma_tools_helper->alma_format_price_from_cents( $alma_step['total_amount'] ),
							date_i18n( get_option( 'date_format' ), $alma_step['due_date'] )
						)
					);
				} else {
					$alma_justify_fees = 'right';
					if ( 'yes' === $upon_trigger_enabled && $alma_eligibility->getInstallmentsCount() <= 4 ) {
						/* translators: %s:  term */
						echo '<span>' . esc_html( sprintf( _n( 'In %s month', 'In %s months', $alma_plan_index - 1, 'alma-gateway-for-woocommerce' ), $alma_plan_index - 1 ) ) . '</span>';
					} else {
						echo '<span>' . esc_html( date_i18n( get_option( 'date_format' ), $alma_step['due_date'] ) ) . '</span>';
					}
					echo wp_kses_post( $alma_tools_helper->alma_format_price_from_cents( $alma_step['total_amount'] ) );
				}
				?>
			</p>
			<?php if ( $alma_display_customer_fee ) { ?>
				<p style="
					display: flex;
					justify-content: <?php echo esc_attr( $alma_justify_fees ); ?>;
					padding: 0 0 4px 0;
					margin: 0 0 4px 0;
					border-bottom: 1px solid lightgrey;
					">
					<span><?php echo esc_html__( 'Included fees:', 'alma-gateway-for-woocommerce' ); ?><?php echo wp_kses_post( $alma_tools_helper->alma_format_price_from_cents( $alma_step['customer_fee'] ) ); ?></span>
				</p>
				<?php
			}
			$alma_plan_index++;
		} // end foreach

		if ( $alma_eligibility->getInstallmentsCount() > 4 ) {
			$alma_cart_helper_builder = new CartHelperBuilder();
			$alma_cart_helper         = $alma_cart_helper_builder->get_instance();

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
				<span><?php echo wp_kses_post( $alma_tools_helper->alma_format_price_from_cents( $alma_cart_helper->get_total_in_cents() ) ); ?></span>
			</p>
			<p style="
			display: flex;
			justify-content: space-between;
			padding: 4px 0;
			margin: 4px 0;
			border-bottom: 1px solid lightgrey;
		">
				<span><?php echo esc_html__( 'Credit cost:', 'alma-gateway-for-woocommerce' ); ?></span>
				<span><?php echo wp_kses_post( $alma_tools_helper->alma_format_price_from_cents( $alma_eligibility->customerTotalCostAmount ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
			</p>
			<?php
			$alma_annual_interest_rate = $alma_eligibility->getAnnualInterestRate();
			if ( ! is_null( $alma_annual_interest_rate ) && $alma_annual_interest_rate > 0 ) {
				?>
				<p style="
			display: flex;
				justify-content: space-between;
				padding: 4px 0;
				margin: 4px 0;
				border-bottom: 1px solid lightgrey;
			">
					<span><?php echo esc_html__( 'Annual Interest Rate:', 'alma-gateway-for-woocommerce' ); ?></span>
					<span><?php echo wp_kses_post( $alma_tools_helper->alma_format_percent_from_bps( $alma_annual_interest_rate ) ); ?></span>
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
				<span><?php echo wp_kses_post( $alma_tools_helper->alma_format_price_from_cents( $alma_eligibility->getCustomerTotalCostAmount() + $alma_cart_helper->get_total_in_cents() ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
			</p>
			<?php
		}
		?>
	</div>
		<?php
	}
	?>
</div>
