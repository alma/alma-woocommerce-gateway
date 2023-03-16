<?php
/**
 * Alma_Plan_Builder.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Models\Alma_Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Plan_Builder
 */
class Alma_Plan_Builder {


	/**
	 * The settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;


	/**
	 * The gateway helper.
	 *
	 * @var Alma_Gateway_Helper
	 */
	protected $gateway_helper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->alma_settings  = new Alma_Settings();
		$this->gateway_helper = new Alma_Gateway_Helper();
	}

	/**
	 * Render the checkout fields.
	 *
	 * @param array  $eligibilities The eligitibilies.
	 * @param array  $eligible_plans The eligibles plans.
	 * @param string $default_plan The default plan.
	 * @return void
	 */
	public function render_checkout_fields( $eligibilities, $eligible_plans, $default_plan = null ) {

		if ( empty( $eligible_plans ) ) {
			$templates = new Alma_Template_Loader();
			$templates->get_template( 'alma-checkout-no-plans.php' );

			return;
		}

		$this->render_fields( $eligibilities, $eligible_plans, $default_plan );
	}

	/**
	 * Render the fields.
	 *
	 * @param array  $eligibilities The eligibilities.
	 * @param array  $eligible_plans The eligible plans.
	 * @param string $default_plan  The default plans.
	 * @return void
	 * @throws Exceptions\Alma_Exception Exception.
	 */
	public function render_fields( $eligibilities, $eligible_plans, $default_plan = null ) {
		echo '<br><br><b><><><>THE DIFFERENT PLANS<><><></b>';
		$templates              = new Alma_Template_Loader();
		$eligible_plans_by_type = $this->order_plans( $eligible_plans );

		foreach ( $eligible_plans_by_type as $type => $eligible_plans ) {
			$templates->get_template(
				'alma-checkout-plans.php',
				array(
					'title'       => $this->gateway_helper->get_alma_gateway_title( $type ),
					'description' => $this->gateway_helper->get_alma_gateway_description( $type ),
				)
			);

			foreach ( $eligible_plans as $plan_key ) {
				$templates->get_template(
					'alma-checkout-plan.php',
					array(
						'plan_key'   => $plan_key,
						'is_checked' => $plan_key === $default_plan,
						'plan_class' => '.' . Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS,
						'plan_id'    => '#' . sprintf( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key ),
						'logo_url'   => Alma_Assets_Helper::get_asset_url( "images/${plan_key}_logo.svg" ),
					),
					'partials'
				);
			}
		}

		echo '<br><br><><><><b>PAYMENT SCHEDULE PLAN</b><><><><br><br>';
		foreach ( $eligibilities as $key => $eligibility ) {
			?>
			<div
					id="<?php echo esc_attr( sprintf( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $key ) ); ?>"
					class="<?php echo esc_attr( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS ); ?>"
					data-gateway-id="<?php echo esc_attr( Alma_Constants_Helper::GATEWAY_ID ); ?>"
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
	}

	/**
	 * Orders the plans.
	 *
	 * @param array $eligible_plans The plans.
	 * @return array    The sorted plans.
	 */
	public function order_plans( $eligible_plans = array() ) {
		$eligible_plans_by_type = array(
			Alma_Constants_Helper::GATEWAY_ID             => array(),
			Alma_Constants_Helper::ALMA_GATEWAY_PAY_LATER => array(),
			Alma_Constants_Helper::ALMA_GATEWAY_PAY_MORE_THAN_FOUR => array(),
		);

		$result = array();

		foreach ( $eligible_plans_by_type as $type => $data ) {
			foreach ( $eligible_plans as $plan ) {
				if ( $this->alma_settings->should_display_plan( $plan, $type ) ) {
					$result[ $type ][] = $plan;
				}
			}
		}

		return $result;
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
		return sprintf( _n( 'In %s month', 'In %s months', $plan_index - 1, 'alma-gateway-for-woocommerce' ), $plan_index - 1 ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
	}
}
