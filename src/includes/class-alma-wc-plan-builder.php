<?php
/**
 * Alma_WC_Plan_Builder.
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/includes
 */

use Alma\API\Endpoints\Results\Eligibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_WC_Plan_Builder
 */
class Alma_WC_Plan_Builder {

	/**
	 * The settings.
	 *
	 * @var Alma_WC_Settings
	 */
	protected $alma_settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->alma_settings = new Alma_WC_Settings();
	}

	/**
	 * Output HTML for a single payment field.
	 *
	 * @param string  $gateway_id Gateway id.
	 * @param string  $plan_key Plan key.
	 * @param boolean $has_radio_button Include a radio button for plan selection.
	 * @param boolean $is_checked Should the radio button be checked.
	 */
	public function payment_field( $gateway_id, $plan_key, $has_radio_button, $is_checked ) {
		$plan_class = '.' . Alma_WC_Helper_Constants::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS;
		$plan_id    = '#' . sprintf( Alma_WC_Helper_Constants::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key );
		$logo_url   = Alma_WC_Helper_Assets::get_asset_url( "images/${plan_key}_logo.svg" );
		?>
		<input
				type="radio"
				value="<?php echo esc_attr( $plan_key ); ?>"
				id="<?php echo esc_attr( $gateway_id ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
				name="alma_fee_plan"
				data-default="<?php echo $is_checked ? '1' : '0'; ?>"
				style="margin-right: 5px;<?php echo ( ! $has_radio_button ) ? 'display:none;' : ''; ?>"
			<?php echo $is_checked ? 'checked' : ''; ?>
				onchange="if (this.checked) { jQuery( '<?php echo esc_js( $plan_class ); ?>' ).hide(); jQuery(this).closest('li.wc_payment_method').find( '<?php echo esc_js( $plan_id ); ?>' ).show() }"
		>
		<label
				class="checkbox"
				style="margin-right: 10px; display: inline;"
				for="<?php echo esc_attr( $gateway_id ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
		>
			<img src="<?php echo esc_attr( $logo_url ); ?>"
				style="float: unset !important; width: auto !important; height: 30px !important;  border: none !important; vertical-align: middle; display: inline-block;"
				alt="
					<?php
					// translators: %s: plan_key alt image.
					echo esc_html( sprintf( __( '%s installments', 'alma-gateway-for-woocommerce' ), $plan_key ) );
					?>
					">
		</label>
		<?php
	}


	/**
	 * Render payment plan with dates.
	 *
	 * @param integer $gateway_id Gateway id.
	 * @param string  $default_plan Plan key.
	 *
	 * @return void
	 */
	public function render_payment_plan( $gateway_id, $default_plan ) {
		$eligibilities = $this->alma_settings->get_cart_eligibilities();

		if ( ! $eligibilities ) {
			return;
		}
		foreach ( $eligibilities as $key => $eligibility ) {
			?>
			<div
					id="<?php echo esc_attr( sprintf( Alma_WC_Helper_Constants::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $key ) ); ?>"
					class="<?php echo esc_attr( Alma_WC_Helper_Constants::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS ); ?>"
					data-gateway-id="<?php echo esc_attr( $gateway_id ); ?>"
					style="
							margin: 0 auto;
					<?php if ( $key !== $default_plan ) { ?>
							display: none;
					<?php } ?>
							"
			>
				<?php
				$this->render_plan( $eligibility );

				if ( $eligibility->getInstallmentsCount() > 4 ) {
					$this->render_payments_timeline( $eligibility );
				}
				?>
			</div>
			<?php
		}
	}


	/**
	 * Renders a payment plan.
	 *
	 * @param Eligibility $eligibility The eligibility object.
	 *
	 * @return void
	 */
	protected function render_plan( $eligibility ) {

		$plan_index   = 1;
		$payment_plan = $eligibility->paymentPlan; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$plans_count  = count( $payment_plan );
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
					$this->render_pay_later_plan( $step );
				} else {
					$justify_fees = 'right';
					$this->render_pnx_plan( $step, $plan_index, $eligibility );
				}
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
					<span><?php echo esc_html__( 'Included fees:', 'alma-gateway-for-woocommerce' ); ?><?php echo wp_kses_post( Alma_WC_Helper_Tools::alma_wc_format_price_from_cents( $step['customer_fee'] ) ); ?></span>
				</p>
				<?php
			}
			$plan_index ++;
		} // end foreach
	}


	/**
	 * Renders pay later plan.
	 *
	 * @param array $step A step (payment occurrence) in the payment plan.
	 *
	 * @return void
	 */
	protected function render_pay_later_plan( $step ) {
		echo wp_kses_post(
			sprintf(
			// translators: %1$s => today_amount (0), %2$s => total_amount, %3$s => i18n formatted due_date.
				__( '%1$s today then %2$s on %3$s', 'alma-gateway-for-woocommerce' ),
				Alma_WC_Helper_Tools::alma_wc_format_price_from_cents( 0 ),
				Alma_WC_Helper_Tools::alma_wc_format_price_from_cents( $step['total_amount'] ),
				date_i18n( get_option( 'date_format' ), $step['due_date'] )
			)
		);
	}

	/**
	 * Renders pnx plan.
	 *
	 * @param array       $step A step (payment occurrence) in the payment plan.
	 * @param integer     $plan_index A counter.
	 * @param Eligibility $eligibility An Eligibility object.
	 *
	 * @return void
	 */
	protected function render_pnx_plan( $step, $plan_index, $eligibility ) {
		if ( 'yes' === $this->alma_settings->payment_upon_trigger_enabled && $eligibility->getInstallmentsCount() <= 4 ) {
			echo '<span>' . esc_html( $this->get_plan_upon_trigger_display_text( $plan_index ) ) . '</span>';
		} else {
			echo '<span>' . esc_html( date_i18n( get_option( 'date_format' ), $step['due_date'] ) ) . '</span>';
		}
		echo wp_kses_post( Alma_WC_Helper_Tools::alma_wc_format_price_from_cents( $step['total_amount'] ) );
	}

	/**
	 * Renders pnx plan with payment upon trigger enabled.
	 *
	 * @param integer $plan_index A counter.
	 *
	 * @return string
	 */
	protected function get_plan_upon_trigger_display_text( $plan_index ) {
		if ( 1 === $plan_index ) {
			return $this->alma_settings->get_display_text();
		}

		// translators: 'In' refers to a number of months, like in 'In one month' or 'In three months'.
		return sprintf( _n( 'In %s month', 'In %s months', $plan_index - 1, 'alma-gateway-for-woocommerce' ), $plan_index - 1 );

	}


	/**
	 * Renders payments timeline for p>4x.
	 *
	 * @param object $eligibility The eligibility object.
	 *
	 * @return void
	 */
	protected function render_payments_timeline( $eligibility ) {

		$cart = new Alma_WC_Model_Cart();
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
			<span><?php echo wp_kses_post( Alma_WC_Helper_Tools::alma_wc_format_price_from_cents( $cart->get_total_in_cents() ) ); ?></span>
		</p>
		<p style="
			display: flex;
			justify-content: space-between;
			padding: 4px 0;
			margin: 4px 0;
			border-bottom: 1px solid lightgrey;
		">
			<span><?php echo esc_html__( 'Credit cost:', 'alma-gateway-for-woocommerce' ); ?></span>
			<span><?php echo wp_kses_post( Alma_WC_Helper_Tools::alma_wc_format_price_from_cents( $eligibility->customerTotalCostAmount ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
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
				<span><?php echo wp_kses_post( Alma_WC_Helper_Tools::alma_wc_format_percent_from_bps( $annual_interest_rate ) ); ?></span>
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
			<span><?php echo wp_kses_post( Alma_WC_Helper_Tools::alma_wc_format_price_from_cents( $eligibility->getCustomerTotalCostAmount() + $cart->get_total_in_cents() ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
		</p>
		<?php
	}
}
