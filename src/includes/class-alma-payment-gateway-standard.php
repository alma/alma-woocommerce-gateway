<?php
/**
 * Alma_Payment_Gateway_Standard.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Admin\Helpers\Alma_Check_Legal_Helper;
use Alma\Woocommerce\Helpers\Alma_Encryptor_Helper;
use Alma\Woocommerce\Helpers\Alma_Payment_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Alma\Woocommerce\Helpers\Alma_Checkout_Helper;
use Alma\Woocommerce\Helpers\Alma_General_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Exceptions\Alma_No_Credentials_Exception;
use Alma\Woocommerce\Admin\Helpers\Alma_General_Helper as Alma_Admin_General_Helper;
use Alma\Woocommerce\Admin\Helpers\Alma_Form_Helper;
use Alma\Woocommerce\Exceptions\Alma_Api_Client_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Merchants_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Plans_Exception;

/**
 * Alma_Payment_Gateway_Standard
 */
class Alma_Payment_Gateway_Standard extends Alma_Payment_Gateway {


	/**
	 * @param $check_basics
	 *
	 * @throws Alma_No_Credentials_Exception
	 */
	public function __construct( $check_basics = true ) {
		$this->id                 = Alma_Constants_Helper::GATEWAY_ID;
		$this->has_fields         = true;
		$this->method_title       = __( 'Payment in instalments and deferred with Alma - 1x 2x 3x 4x, D+15 or D+30', 'alma-gateway-for-woocommerce' );
		$this->method_description = __( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.', 'alma-gateway-for-woocommerce' );
		parent::__construct( $check_basics );
	}

	/**
	 * Add filters.
	 *
	 * @return void
	 */
	public function add_filters() {
		add_filter(
			'woocommerce_available_payment_gateways',
			array(
				$this->gateway_helper,
				'woocommerce_available_payment_gateways',
			)
		);

		add_filter( 'woocommerce_gateway_title', array( $this->gateway_helper, 'woocommerce_gateway_title' ), 10, 2 );
		add_filter( 'allowed_redirect_hosts', array( $this->general_helper, 'alma_domains_whitelist' ) );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	/**
	 *  Add the actions.
	 *
	 * @return void
	 */
	public function add_actions() {
		add_action( 'woocommerce_before_checkout_process', array( $this->checkout_helper, 'woocommerce_checkout_process' ), 1 );
		$payment_upon_trigger_helper = new Alma_Payment_Upon_Trigger();
		add_action(
			'woocommerce_order_status_changed',
			array(
				$payment_upon_trigger_helper,
				'woocommerce_order_status_changed',
			),
			10,
			3
		);

	}
	/**
	 * Custom payment fields for a payment gateway.
	 * (We have three payment gateways : "alma", "alma_pay_later", and "alma_pnx_plus_4")
	 */
	public function payment_fields() {
		$this->checkout_helper->render_nonce_field( $this->id );

		// We get the eligibilites.
		$eligibilities  = $this->alma_settings->get_cart_eligibilities();
		$eligible_plans = $this->alma_settings->get_eligible_plans_keys_for_cart( $eligibilities );

		if (
				! empty( $this->alma_settings->settings['display_in_page'] )
			&& $this->alma_settings->settings['display_in_page'] == 'yes'
		) {
			foreach ( $eligible_plans as $key => $plan ) {
				if (
					in_array(
						$plan,
						array( 'general_2_0_0', 'general_3_0_0', 'general_4_0_0' )
					) ) {
					unset( $eligible_plans[ $key ] );
				}
			}
		}

		$default_plan = $this->gateway_helper->get_default_plan( $eligible_plans );

		$this->plan_builder->render_checkout_fields( $eligibilities, $eligible_plans, $this->id, $default_plan );
	}


	/**
	 *  Get the current tab and section.
	 *
	 * @return array
	 */
	protected function get_tab_and_section() {
		global $current_tab, $current_section;
		$tab     = $current_tab;
		$section = $current_section;

		if (
			(
				empty( $tab )
				|| empty( $section )
			)
			&& ! empty( $_SERVER['QUERY_STRING'] )
		) {
			$query_parts = explode( '&', $_SERVER['QUERY_STRING'] );

			foreach ( $query_parts as $args ) {
				$query_args = explode( '=', $args );

				if ( count( $query_args ) == 2 ) {
					switch ( $query_args['0'] ) {
						case 'tab':
							$tab = $query_args['1'];
							break;
						case 'section':
							$section = $query_args['1'];
							break;
						default:
							break;
					}
				}
			}
		}

		return array( $tab, $section );
	}

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 */
	public function get_icon() {
		return Alma_Assets_Helper::get_icon( $this->get_title(), $this->id, Alma_Constants_Helper::ALMA_SHORT_LOGO_PATH );
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array The result.
	 */
	public function process_payment( $order_id ) {
		$error_msg = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );

		try {
			$wc_order = $this->order_helper->get_order( $order_id );
			// We ignore the nonce verification because process_payment is called after validate_fields.
			$fee_plan = $this->alma_settings->build_fee_plan( $_POST[ Alma_Constants_Helper::ALMA_FEE_PLAN ] ); // phpcs:ignore WordPress.Security.NonceVerification
			$payment  = $this->alma_payment_helper->create_payments( $wc_order, $fee_plan );

			// Redirect user to our payment page.
			return array(
				'result'   => Alma_Constants_Helper::SUCCESS,
				'redirect' => $payment->url,
			);
		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getTrace() );
			wc_add_notice( $error_msg, Alma_Constants_Helper::ERROR );
			return array( 'result' => Alma_Constants_Helper::ERROR );
		}
	}

	/**
	 * Validate payment fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$error_msg = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );

		$alma_fee_plan = $this->checkout_helper->get_chosen_alma_fee_plan( $this->id );

		if ( ! $alma_fee_plan ) {
			wc_add_notice( $error_msg, Alma_Constants_Helper::ERROR );

			return false;
		}

		$is_alma_payment = $this->checkout_helper->is_alma_payment_method( $this->id );

		if ( ! $is_alma_payment ) {
			wc_add_notice( $error_msg, Alma_Constants_Helper::ERROR );
			return false;
		}
		$allowed_values = array_map( 'strval', $this->alma_settings->get_eligible_plans_keys_for_cart() );

		if ( ! in_array( $alma_fee_plan, $allowed_values, true ) ) {
			wc_add_notice( '<strong>Fee plan</strong> is invalid.', Alma_Constants_Helper::ERROR );

			return false;
		}

		return true;
	}


	/**
	 * Generate a select `alma_fee` Input HTML (based on `select_alma_fee_plan` field definition).
	 * Warning: use only one `select_alma_fee_plan` type in your form (script & css are inlined here)
	 *
	 * @param mixed $key as field key.
	 * @param mixed $data as field configuration.
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
	 * Override WC_Settings_API Generate Title HTML.
	 * add css, description_css, table_css, description_class & table_class definitions.
	 *
	 * @param mixed $key as string form field key.
	 * @param mixed $data as field definition array.
	 *
	 * @return string
	 * @since  1.0.0
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
		<h3 class="wc-settings-sub-title <?php echo esc_attr( $data['class'] ); ?>"
			style="<?php echo esc_attr( $data['css'] ); ?>"
			id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></h3>
		<?php if ( ! empty( $data['description'] ) ) : ?>
			<div class="<?php echo esc_attr( $data['description_class'] ); ?>"
				style="<?php echo esc_attr( $data['description_css'] ); ?>"><?php echo wp_kses_post( $data['description'] ); ?></div>
		<?php endif; ?>
	<table class="form-table <?php echo esc_attr( $data['table_class'] ); ?>"
		style="<?php echo esc_attr( $data['table_css'] ); ?>">
		<?php

		return ob_get_clean();
	}

	/**
	 * Encrypt the api keys.
	 *
	 * @param array $post_data The form data.
	 * @return array The form data.
	 */
	protected function encrypt_keys( $post_data ) {
		if ( ! empty( $post_data['woocommerce_alma_live_api_key'] ) ) {
			$post_data['woocommerce_alma_live_api_key'] = $this->encryption_helper->encrypt( $post_data['woocommerce_alma_live_api_key'] );
		}

		if ( ! empty( $post_data['woocommerce_alma_test_api_key'] ) ) {
			$post_data['woocommerce_alma_test_api_key'] = $this->encryption_helper->encrypt( $post_data['woocommerce_alma_test_api_key'] );
		}

		return $post_data;
	}




} // end \Alma_Pay_Gateway class
