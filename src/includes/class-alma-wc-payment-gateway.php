<?php
/**
 * Alma WooCommerce payment gateway
 *
 * @package Alma_WooCommerce_Gateway
 * @noinspection HtmlUnknownTarget
 */

use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

/**
 * Alma_WC_Payment_Gateway
 */
class Alma_WC_Payment_Gateway extends WC_Payment_Gateway {
	const GATEWAY_ID = 'alma';

	const ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE = 'alma-payment-plan-table-%s-installments';
	const ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS   = 'js-alma-payment-plan-table';
	const AMOUNT_PLAN_KEY_REGEX               = '#^(min|max)_amount_general_[0-9]+_[0-9]+_[0-9]+$#';
	const ENABLED_PLAN_KEY_REGEX              = '#^enabled_general_([0-9]+_[0-9]+_[0-9]+)$#';
	const ALMA_GATEWAY_PAY_LATER              = 'alma_pay_later';
	const ALMA_GATEWAY_PAY_MORE_THAN_FOUR     = 'alma_pnx_plus_4';


	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;
	/**
	 * Checkout Helper.
	 *
	 * @var Alma_WC_Checkout_Helper
	 */
	private $checkout_helper;

	/**
	 * __construct
	 */
	public function __construct() {
		$this->id                 = self::GATEWAY_ID;
		$this->has_fields         = true;
		$this->method_title       = __( 'Payment in instalments and deferred with Alma - 2x 3x 4x, D+15 or D+30', 'alma-gateway-for-woocommerce' );
		$this->method_description = __( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.', 'alma-gateway-for-woocommerce' );

		$this->logger          = new Alma_WC_Logger();
		$this->checkout_helper = new Alma_WC_Checkout_Helper();

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		add_action( 'woocommerce_before_checkout_process', array( $this, 'woocommerce_checkout_process' ), 1 );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'woocommerce_available_payment_gateways' ), 10, 1 );
	}

	/**
	 * Get option from DB.
	 *
	 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
	 * This is overridden so that values saved in cents in the DB can be shown in euros to the user.
	 *
	 * @param string $key Option key.
	 * @param mixed  $empty_value Value when empty.
	 *
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) {
		$option = parent::get_option( $key, $empty_value );

		if ( $this->is_amount_plan_key( $key ) ) {
			return strval( alma_wc_price_from_cents( $option ) );
		}

		return $option;
	}

	/**
	 * Sync & Check if 'enabled' & 'min / max amounts' are set & ok according merchant configuration
	 * Add installments counts & deferred days / months into settings
	 */
	protected function sync_fee_plans_settings() {
		foreach ( alma_wc_plugin()->settings->get_allowed_fee_plans() as $fee_plan ) {
			$plan_key           = $fee_plan->getPlanKey();
			$default_min_amount = $fee_plan->min_purchase_amount;
			$default_max_amount = $fee_plan->max_purchase_amount;
			$min_key            = "min_amount_$plan_key";
			$max_key            = "max_amount_$plan_key";
			$enabled_key        = "enabled_$plan_key";

			if ( ! isset( $this->settings[ $min_key ] ) || $this->settings[ $min_key ] < $default_min_amount || $this->settings[ $min_key ] > $default_max_amount ) {
				$this->settings[ $min_key ] = $default_min_amount;
			}
			if ( ! isset( $this->settings[ $max_key ] ) || $this->settings[ $max_key ] > $default_max_amount || $this->settings[ $max_key ] < $default_min_amount ) {
				$this->settings[ $max_key ] = $default_max_amount;
			}
			if ( ! isset( $this->settings[ $enabled_key ] ) ) {
				$this->settings[ $enabled_key ] = alma_wc_plugin()->settings->default_settings()['selected_fee_plan'] === $plan_key ? 'yes' : 'no';
			}
			$this->settings[ "deferred_months_$plan_key" ]    = $fee_plan->getDeferredMonths();
			$this->settings[ "deferred_days_$plan_key" ]      = $fee_plan->getDeferredDays();
			$this->settings[ "installments_count_$plan_key" ] = $fee_plan->getInstallmentsCount();
		}

	}

	/**
	 * Get Alma icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon_url = alma_wc_plugin()->get_asset_url( 'images/alma_logo.svg' );
		$icon     = '<img src="' . WC_HTTPS::force_https_url( $icon_url ) . '" alt="' . esc_attr( $this->get_title() ) . '" style="width: auto !important; height: 25px !important; border: none !important;">';

		return apply_filters( 'alma_wc_gateway_icon', $icon, $this->id );
	}

	/**
	 * Init admin settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = Alma_WC_Admin_Form::init_form_fields();
	}

	/**
	 * Init settings.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->update_settings_from_merchant();
		alma_wc_plugin()->settings->update_from( $this->settings );
	}

	/**
	 * Processes and saves options.
	 *
	 * @return bool
	 */
	public function process_admin_options() {
		$previously_saved = parent::process_admin_options();

		$this->convert_amounts_to_cents();
		$this->update_settings_from_merchant();
		alma_wc_plugin()->settings->update_from( $this->settings );
		alma_wc_plugin()->force_check_settings();

		return $previously_saved && update_option( $this->get_option_key(), $this->settings );
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 * @override
	 */
	public function is_available() {
		if ( wc()->cart === null ) {
			return parent::is_available();
		}

		if ( ! alma_wc_plugin()->check_currency() ) {
			return false;
		}

		if ( ! alma_wc_plugin()->is_there_eligibility_in_cart() ) {
			return false;
		}

		if ( $this->cart_contains_excluded_category() ) {
			return false;
		}

		return $this->is_cart_eligible() && parent::is_available();
	}

	/**
	 * Output HTML for a single payment field.
	 *
	 * @param string  $gateway_id          Gateway id.
	 * @param string  $plan_key            Plan key.
	 * @param boolean $has_radio_button    Include a radio button for plan selection.
	 * @param boolean $is_checked          Should the radio button be checked.
	 */
	private function payment_field( $gateway_id, $plan_key, $has_radio_button, $is_checked ) {
		$plan_class = '.' . self::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS;
		$plan_id    = '#' . sprintf( self::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key );
		$logo_url   = alma_wc_plugin()->get_asset_url( "images/${plan_key}_logo.svg" );
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
	 * Custom payment fields for a payment gateway.
	 * (We have three payment gateways : "alma", "alma_pay_later", and "alma_pnx_plus_4")
	 */
	public function payment_fields() {
		echo wp_kses_post( $this->get_description() );
		$this->checkout_helper->render_nonce_field();

		$gateway_id = $this->id;

		$eligible_plans = alma_wc_plugin()->get_eligible_plans_keys_for_cart();
		usort( $eligible_plans, 'alma_wc_usort_plans_keys' );
		$default_plan = self::get_default_plan( $eligible_plans );

		$payment_fields = array();
		foreach ( $eligible_plans as $plan_key ) {
			if ( $this->should_display_plan( $plan_key, $gateway_id ) ) {
				$payment_fields[] = $plan_key;
			}
		}
		$is_multiple_plans = count( $payment_fields ) > 1;
		if ( $is_multiple_plans ) {
			?>
			<p><?php echo esc_html__( 'Choose your payment method', 'alma-gateway-for-woocommerce' ); ?><span class="required">*</span></p>
			<?php
		} else {
			?>
			<br />
			<?php
		}

		foreach ( $payment_fields as $plan_key ) {
			$this->payment_field( $gateway_id, $plan_key, $is_multiple_plans, $plan_key === $default_plan );
		}
		?>
		<p>
			<?php
			$this->render_payment_plan( $gateway_id, $default_plan );
			?>
		</p>
		<?php
	}

	/**
	 * Tells if we should display this fee plan for this gateway. (we have three alma payment gateways)
	 *
	 * @param string $plan_key Plan key.
	 * @param string $gateway_id Gateway id.
	 *
	 * @return bool
	 */
	public function should_display_plan( $plan_key, $gateway_id ) {
		$alma_settings = alma_wc_plugin()->settings;
		switch ( $gateway_id ) {
			case self::GATEWAY_ID:
				$should_display = in_array( $alma_settings->get_installments_count( $plan_key ), array( 2, 3, 4 ), true );
				break;
			case self::ALMA_GATEWAY_PAY_LATER:
				$should_display = (
					$alma_settings->get_installments_count( $plan_key ) === 1
					&& ( $alma_settings->get_deferred_days( $plan_key ) !== 0 || $alma_settings->get_deferred_months( $plan_key ) !== 0 )
				);
				break;
			case self::ALMA_GATEWAY_PAY_MORE_THAN_FOUR:
				$should_display = ( $alma_settings->get_installments_count( $plan_key ) > 4 );
				break;
			default:
				return false;
		}

		return $should_display;
	}

	/**
	 * Validate payment fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$alma_fee_plan = $this->checkout_helper->get_chosen_alma_fee_plan();
		if ( ! $alma_fee_plan ) {
			return false;
		}
		$allowed_values = array_map( 'strval', alma_wc_plugin()->get_eligible_plans_keys_for_cart() );
		if ( ! in_array( $alma_fee_plan, $allowed_values, true ) ) {
			wc_add_notice( '<strong>Fee plan</strong> is invalid.', 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$error_msg = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			wc_add_notice( $error_msg, 'error' );

			return array(
				'result' => 'error',
			);
		}
		try {
			$fee_plan_definition = $this->get_fee_plan_definition( $this->checkout_helper->get_chosen_alma_fee_plan() );
		} catch ( Exception $e ) {
			$this->logger->log_stack_trace( 'Error while creating payment: ', $e );
			wc_add_notice( $error_msg, 'error' );

			return array( 'result' => 'error' );
		}

		try {
			$payment = $alma->payments->create(
				Alma_WC_Model_Payment::get_payment_payload_from_order( $order_id, $fee_plan_definition )
			);
		} catch ( RequestError $e ) {
			$this->logger->log_stack_trace( 'Error while creating payment: ', $e );
			wc_add_notice( $error_msg, 'error' );

			return array( 'result' => 'error' );
		}

		// Redirect user to our payment page.
		return array(
			'result'   => 'success',
			'redirect' => $payment->url,
		);
	}

	/**
	 * Redirect to cart with error.
	 *
	 * @param string $error_msg Error message.
	 */
	private function redirect_to_cart_with_error( $error_msg ) {
		wc_add_notice( $error_msg, 'error' );

		$cart_url = wc_get_cart_url();
		wp_safe_redirect( $cart_url );
		exit();
	}

	/**
	 * Validate payment on customer return.
	 *
	 * @param string $payment_id Payment Id.
	 */
	public function validate_payment_on_customer_return( $payment_id ) {
		$order     = null;
		$error_msg = __( 'There was an error when validating your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );
		try {
			$order = Alma_WC_Payment_Validator::validate_payment( $payment_id );
		} catch ( Alma_WC_Payment_Validation_Error $e ) {
			$this->redirect_to_cart_with_error( $error_msg );
		} catch ( \Exception $e ) {
			$this->redirect_to_cart_with_error( $e->getMessage() );
		}

		if ( ! $order ) {
			$this->redirect_to_cart_with_error( $error_msg );
		}

		// Redirect user to the order confirmation page.
		$return_url = $this->get_return_url( $order->get_wc_order() );
		wp_safe_redirect( $return_url );
		exit();
	}

	/**
	 * Validate payment from ipn.
	 *
	 * @param string $payment_id Payment Id.
	 */
	public function validate_payment_from_ipn( $payment_id ) {
		try {
			Alma_WC_Payment_Validator::validate_payment( $payment_id );
		} catch ( \Exception $e ) {
			status_header( 500 );
			wp_send_json( array( 'error' => $e->getMessage() ) );
		}

		wp_send_json( array( 'success' => true ) );
	}

	/**
	 * Render payment plan with dates.
	 *
	 * @param integer $gateway_id   Gateway id.
	 * @param string  $default_plan Plan key.
	 *
	 * @return void
	 */
	private function render_payment_plan( $gateway_id, $default_plan ) {
		$eligibilities = alma_wc_plugin()->get_cart_eligibilities();

		if ( ! $eligibilities ) {
			return;
		}
		foreach ( $eligibilities as $key => $eligibility ) {
			?>
			<div
					id="<?php echo esc_attr( sprintf( self::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $key ) ); ?>"
					class="<?php echo esc_attr( self::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS ); ?>"
					data-gateway-id="<?php echo esc_attr( $gateway_id ); ?>"
					style="
							margin: 0 auto;
					<?php if ( $key !== $default_plan ) { ?>
							display: none;
					<?php	} ?>
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
	private function render_plan( $eligibility ) {

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
			<?php	} ?>
			<?php if ( $plan_index === $plans_count || $display_customer_fee ) { ?>
					padding-bottom: 0;
					margin-bottom: 0;
			<?php	} else { ?>
					border-bottom: 1px solid lightgrey;
			<?php	} ?>
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
					<span><?php echo esc_html__( 'Included fees:', 'alma-gateway-for-woocommerce' ); ?> <?php echo wp_kses_post( alma_wc_format_price_from_cents( $step['customer_fee'] ) ); ?></span>
				</p>
				<?php
			}
			$plan_index++;
		} // end foreach
	}

	/**
	 * Renders pay later plan.
	 *
	 * @param array $step A step (payment occurrence) in the payment plan.
	 * @return void
	 */
	private function render_pay_later_plan( $step ) {
		echo wp_kses_post(
			sprintf(
				// translators: %1$s => today_amount (0), %2$s => total_amount, %3$s => i18n formatted due_date.
				__( '%1$s today then %2$s on %3$s', 'alma-gateway-for-woocommerce' ),
				alma_wc_format_price_from_cents( 0 ),
				alma_wc_format_price_from_cents( $step['total_amount'] ),
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
	 * @return void
	 */
	private function render_pnx_plan( $step, $plan_index, $eligibility ) {
		if ( 'yes' === $this->settings['payment_upon_trigger_enabled'] && $eligibility->getInstallmentsCount() <= 4 ) {
			echo '<span>' . esc_html( $this->get_plan_upon_trigger_display_text( $plan_index ) ) . '</span>';
		} else {
			echo '<span>' . esc_html( date_i18n( get_option( 'date_format' ), $step['due_date'] ) ) . '</span>';
		}
		echo wp_kses_post( alma_wc_format_price_from_cents( $step['total_amount'] ) );
	}

	/**
	 * Renders pnx plan with payment upon trigger enabled.
	 *
	 * @param integer $plan_index A counter.
	 *
	 * @return string
	 */
	private function get_plan_upon_trigger_display_text( $plan_index ) {
		if ( 1 === $plan_index ) {
			return Alma_WC_Payment_Upon_Trigger::get_display_text();
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
	private function render_payments_timeline( $eligibility ) {

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
			<span><?php echo wp_kses_post( alma_wc_format_price_from_cents( $cart->get_total_in_cents() ) ); ?></span>
		</p>
		<p style="
			display: flex;
			justify-content: space-between;
			padding: 4px 0;
			margin: 4px 0;
			border-bottom: 1px solid lightgrey;
		">
			<span><?php echo esc_html__( 'Credit cost:', 'alma-gateway-for-woocommerce' ); ?></span>
			<span><?php echo wp_kses_post( alma_wc_format_price_from_cents( $eligibility->customerTotalCostAmount ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
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
				<span><?php echo wp_kses_post( alma_wc_format_percent_from_bps( $annual_interest_rate ) ); ?></span>
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
			<span><?php echo wp_kses_post( alma_wc_format_price_from_cents( $eligibility->getCustomerTotalCostAmount() + $cart->get_total_in_cents() ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName ?></span>
		</p>
		<?php
	}



	/**
	 * Gets default plan according to eligible pnx list.
	 *
	 * @param string[] $plans the list of eligible pnx.
	 *
	 * @return string|null
	 */
	private static function get_default_plan( $plans ) {
		if ( ! count( $plans ) ) {
			return null;
		}
		if ( in_array( Alma_WC_Settings::DEFAULT_FEE_PLAN, $plans, true ) ) {
			return Alma_WC_Settings::DEFAULT_FEE_PLAN;
		}

		return end( $plans );
	}

	/**
	 * Generate a select `alma_fee` Input HTML (based on `select_alma_fee_plan` field definition).
	 * Warning: use only one `select_alma_fee_plan` type in your form (script & css are inlined here)
	 *
	 * @param  mixed $key as field key.
	 * @param  mixed $data as field configuration.
	 *
	 * @return string
	 * @see WC_Settings_API::generate_settings_html() that calls dynamically generate_<field_type>_html
	 * @noinspection PhpUnused
	 */
	public function generate_select_alma_fee_plan_html( $key, $data ) {
		?>
		<script>
			var select_alma_fee_plans_id = select_alma_fee_plans_id || '<?php echo esc_attr( $this->get_field_key( $key ) ); ?>';
		</script>
		<style>
			.alma_option_enabled::after {
				content: ' (<?php echo esc_attr__( 'enabled', 'alma-gateway-for-woocommerce' ); ?>)';
			}
			.alma_option_disabled::after {
				content: ' (<?php echo esc_attr__( 'disabled', 'alma-gateway-for-woocommerce' ); ?>)';
			}

		</style>
		<?php
		return parent::generate_select_html( $key, $data );
	}

	/**
	 * Generates a html `i18n` Input HTML.
	 *
	 * @param string         $key The field name.
	 * @param array|string[] $data The configuration for this field.
	 *
	 * @return false|string
	 * @see WC_Settings_API::generate_text_html()
	 * @noinspection PhpUnused
	 */
	public function generate_text_alma_i18n_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'lang_list'         => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top" class="alma-i18n-parent" style="display:none;">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input alma-i18n <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'] ); ?> <?php echo $this->get_custom_attribute_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?> />
					<select class="list_lang_title" style="width:auto;margin-left:10px;line-height:28px;">
					<?php
					foreach ( $data['lang_list'] as $code => $label ) {
						$selected = Alma_WC_Admin_Internationalization_Front_Helper::is_lang_selected( esc_attr( $code ) ) ? 'selected="selected"' : '';
						print '<option value="' . esc_attr( $code ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $label ) . '</option>';
					}
					?>
					</select>
					<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Override WC_Settings_API Generate Title HTML.
	 * add css, description_css, table_css, description_class & table_class definitions.
	 *
	 * @param  mixed $key as string form field key.
	 * @param  mixed $data as field definition array.
	 * @since  1.0.0
	 * @return string
	 */
	public function generate_title_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'class'             => '',
			'css'               => '',
			'table_class'       => '',
			'table_css'         => '',
			'description_class' => '',
			'description_css'   => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		</table>
		<h3 class="wc-settings-sub-title <?php echo esc_attr( $data['class'] ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></h3>
		<?php if ( ! empty( $data['description'] ) ) : ?>
			<div class="<?php echo esc_attr( $data['description_class'] ); ?>" style="<?php echo esc_attr( $data['description_css'] ); ?>"><?php echo wp_kses_post( $data['description'] ); ?></div>
		<?php endif; ?>
	<table class="form-table <?php echo esc_attr( $data['table_class'] ); ?>" style="<?php echo esc_attr( $data['table_css'] ); ?>">
		<?php

		return ob_get_clean();
	}

	/**
	 * Check if there is some excluded products into cart
	 *
	 * @return bool
	 */
	private function cart_contains_excluded_category() {
		if ( wc()->cart === null ) {
			return false;
		}
		if (
			array_key_exists( 'excluded_products_list', $this->settings ) &&
			is_array( $this->settings['excluded_products_list'] ) &&
			count( $this->settings['excluded_products_list'] ) > 0
		) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];

				foreach ( $this->settings['excluded_products_list'] as $category_slug ) {
					if ( has_term( $category_slug, 'product_cat', $product_id ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Check if cart eligibilities has at least one eligible plan.
	 *
	 * @return bool
	 */
	private function is_cart_eligible() {
		$eligibilities = alma_wc_plugin()->get_cart_eligibilities();

		if ( ! $eligibilities ) {
			return false;
		}

		$is_eligible = false;

		foreach ( $eligibilities as $plan ) {
			$is_eligible = $is_eligible || $plan->isEligible; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		return $is_eligible;
	}

	/**
	 * After settings have been updated, override min/max amounts to convert them to cents.
	 */
	private function convert_amounts_to_cents() {
		$post_data = $this->get_post_data();
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( $this->is_amount_plan_key( $key ) ) {
				try {
					$amount                 = $this->get_field_value( $key, $field, $post_data );
					$this->settings[ $key ] = alma_wc_price_to_cents( $amount );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}
	}

	/**
	 * If API is configured with its keys, try to fetch info from merchant account.
	 */
	private function update_settings_from_merchant() {
		if ( alma_wc_plugin()->settings->need_api_key() ) {
			$this->reset_merchant_settings();

			return;
		}

		try {
			$merchant                      = alma_wc_plugin()->get_merchant();
			$this->settings['merchant_id'] = $merchant->id;
		} catch ( RequestError $e ) {
			$this->reset_merchant_settings();
			alma_wc_plugin()->handle_settings_exception( $e );
			return;
		}
		$this->sync_fee_plans_settings();

		$this->disable_unavailable_fee_plans_config();
	}

	/**
	 * Reset merchant & amount settings
	 */
	private function reset_merchant_settings() {
		// reset merchant id.
		$this->settings['merchant_id'] = null;
		// reset min and max amount for all plans.
		foreach ( array_keys( $this->settings ) as $key ) {
			if ( $this->is_amount_plan_key( $key ) ) {
				$this->settings[ $key ] = null;
			}
		}
	}

	/**
	 * Check if key match amount key format
	 *
	 * @param string $key As setting's key.
	 *
	 * @return boolean
	 */
	private function is_amount_plan_key( $key ) {
		return preg_match( self::AMOUNT_PLAN_KEY_REGEX, $key ) > 0;
	}

	/**
	 * Force disable not available fee_plans to prevent showing them in checkout.
	 */
	private function disable_unavailable_fee_plans_config() {
		$allowed_installments = alma_wc_plugin()->settings->get_allowed_plans_keys();
		if ( ! $allowed_installments ) {
			return;
		}
		foreach ( array_keys( $this->settings ) as $key ) {
			if ( preg_match( self::ENABLED_PLAN_KEY_REGEX, $key, $matches ) ) {
				// force disable not available fee_plans to prevent showing them in checkout.
				if ( ! in_array( intval( $matches[1] ), $allowed_installments, true ) ) {
					$this->settings[ "enabled_${matches[1]}" ] = 'no';
				}
			}
		}
	}

	/**
	 * Populate array with plan settings.
	 *
	 * @param string $plan_key The plan key.
	 *
	 * @return array
	 *
	 * @throws Exception If required keys not found.
	 */
	private function get_fee_plan_definition( $plan_key ) {
		$definition = array();
		if ( ! isset( $this->settings[ "installments_count_$plan_key" ] ) ) {
			throw new Exception( "installments_count_$plan_key not set" );
		}
		if ( ! isset( $this->settings[ "deferred_days_$plan_key" ] ) ) {
			throw new Exception( "deferred_days_$plan_key not set" );
		}
		if ( ! isset( $this->settings[ "deferred_months_$plan_key" ] ) ) {
			throw new Exception( "deferred_months_$plan_key not set" );
		}
		$definition['installments_count'] = $this->settings[ "installments_count_$plan_key" ];
		$definition['deferred_days']      = $this->settings[ "deferred_days_$plan_key" ];
		$definition['deferred_months']    = $this->settings[ "deferred_months_$plan_key" ];
		$definition['plan_key']           = $plan_key;

		return $definition;
	}

	/**
	 * AJAX when validating the checkout.
	 * If the payment method used is like "alma_****", then rename it to "alma" and let WC do the payment process.
	 *
	 * @return void
	 */
	public function woocommerce_checkout_process() {
		if ( $this->checkout_helper->is_alma_payment_method() ) {
			$_POST['payment_method'] = self::GATEWAY_ID;
		}
	}

	/**
	 * Filter available_gateways to add "alma_pay_later" and "alma_pnx_plus_4".
	 *
	 * @param array $_available_gateways The list of available gateways.
	 * @return array
	 */
	public function woocommerce_available_payment_gateways( $_available_gateways ) {

		if ( is_admin() ) {
			return $_available_gateways;
		}

		$new_available_gateways = array();
		foreach ( $_available_gateways as $key => $gateway ) {
			$new_available_gateways[ $key ] = $gateway;

			if ( 'alma' === $gateway->id ) {
				$new_available_gateways = array_merge( $new_available_gateways, $this->build_new_available_gateways( $gateway ) );
			}
		}

		return $new_available_gateways;
	}

	/**
	 * Add two alma payment gateways if needed (pay_later and pnx_plus_4)
	 *
	 * Fields "title" and "description" will then be overwritten by filters :
	 * "woocommerce_gateway_title" and "woocommerce_gateway_description".
	 *
	 * @param object $gateway Alma WC payment gateway.
	 *
	 * @return array
	 */
	private function build_new_available_gateways( $gateway ) {
		$new_available_gateways = array();

		if ( $this->is_there_available_plan_for_this_gateway( self::ALMA_GATEWAY_PAY_LATER ) ) {
			$tmp_gateway                                = clone $gateway;
			$tmp_gateway->id                            = self::ALMA_GATEWAY_PAY_LATER;
			$new_available_gateways[ $tmp_gateway->id ] = $tmp_gateway;
		}

		if ( $this->is_there_available_plan_for_this_gateway( self::ALMA_GATEWAY_PAY_MORE_THAN_FOUR ) ) {
			$tmp_gateway                                = clone $gateway;
			$tmp_gateway->id                            = self::ALMA_GATEWAY_PAY_MORE_THAN_FOUR;
			$new_available_gateways[ $tmp_gateway->id ] = $tmp_gateway;
		}

		return $new_available_gateways;
	}

	/**
	 * Test if is there available plan for given payment method
	 *
	 * @param string $gateway_id As payment method name.
	 *
	 * @return bool
	 */
	private function is_there_available_plan_for_this_gateway( $gateway_id ) {
		foreach ( alma_wc_plugin()->get_eligible_plans_keys_for_cart() as $plan_key ) {
			if ( $this->should_display_plan( $plan_key, $gateway_id ) ) {
				return true;
			}
		}

		return false;
	}

}
