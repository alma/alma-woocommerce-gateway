<?php
/**
 * FormFieldsHelper.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Admin/Helpers
 * @namespace Alma\Woocommerce\Admin\Helpers
 */

namespace Alma\Woocommerce\Admin\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\API\Entities\FeePlan;
use Alma\Woocommerce\Admin\Builders\FormHtmlBuilder;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Helpers\AssetsHelper;
use Alma\Woocommerce\Helpers\BlockHelper;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\FeePlanHelper;
use Alma\Woocommerce\Helpers\InternationalizationHelper;
use Alma\Woocommerce\Helpers\PluginHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Services\PaymentUponTriggerService;


/**
 * FormFieldsHelper.
 */
class FormFieldsHelper {


	/**
	 * The settings.
	 *
	 * @var AlmaSettings
	 */
	protected $settings_helper;

	/**
	 * Payment Upon trigger.
	 *
	 * @var PaymentUponTriggerService
	 */
	protected $payment_upon_trigger;

	/**
	 * Fee plan helper.
	 *
	 * @var FeePlanHelper
	 */
	protected $fee_plan_helper;

	/**
	 * Plugin helper.
	 *
	 * @var PluginHelper
	 */
	protected $plugin_helper;

	/**
	 * Block helper.
	 *
	 * @var BlockHelper
	 */
	protected $block_helper;

	/**
	 * Tools helper.
	 *
	 * @var ToolsHelper
	 */
	protected $tools_helper;

	/**
	 * Internationalization Helper.
	 *
	 * @var InternationalizationHelper
	 */
	protected $internationalization_helper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings_helper             = new AlmaSettings();
		$this->payment_upon_trigger        = new PaymentUponTriggerService();
		$this->fee_plan_helper             = new FeePlanHelper();
		$this->plugin_helper               = new PluginHelper();
		$this->block_helper                = new BlockHelper();
		$tools_helper_builder              = new ToolsHelperBuilder();
		$this->tools_helper                = $tools_helper_builder->get_instance();
		$this->internationalization_helper = new InternationalizationHelper();
	}


	/**
	 * Inits enabled Admin field.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array[]
	 */
	public function init_enabled_field( $default_settings ) {
		return array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'alma-gateway-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable monthly payments with Alma', 'alma-gateway-for-woocommerce' ),
				'default' => $default_settings['enabled'],
			),
		);
	}

	/**
	 * Inits test & live api keys fields.
	 *
	 * @param string $keys_title as section title.
	 * @param array  $default_settings as default settings.
	 *
	 * @return array[]
	 */
	public function init_api_key_fields( $keys_title, $default_settings ) {
		$merchant_infos_description = $this->get_merchant_infos_description();

		return array(
			'keys_section' => array(
				'title'       => '<hr>' . $keys_title,
				'type'        => 'title',
				/* translators: %s Alma security URL */
				'description' => sprintf( __( 'You can find your API keys on <a href="%s" target="_blank">your Alma dashboard</a>', 'alma-gateway-for-woocommerce' ), AssetsHelper::get_alma_dashboard_url( $this->settings_helper->get_environment(), 'security' ) ),
			),
			'live_api_key' => array(
				'title' => __( 'Live API key', 'alma-gateway-for-woocommerce' ),
				'type'  => 'password',
			),
			'test_api_key' => array(
				'title' => __( 'Test API key', 'alma-gateway-for-woocommerce' ),
				'type'  => 'password',
			),
			'environment'  => array(
				'title'       => __( 'API Mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'select',
				/* translators: %s Merchant description */
				'description' => sprintf( __( 'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.<br> %s', 'alma-gateway-for-woocommerce' ), $merchant_infos_description ),
				'default'     => $default_settings['environment'],
				'options'     => array(
					'test' => __( 'Test', 'alma-gateway-for-woocommerce' ),
					'live' => __( 'Live', 'alma-gateway-for-woocommerce' ),
				),
			),
		);
	}

	/**
	 * Get the merchant info's description.
	 *
	 * @return string
	 */
	protected function get_merchant_infos_description() {
		$key = 'test';

		if ( $this->settings_helper->get_environment() === 'live' ) {
			$key = 'live';
		}

		$description = '';

		if ( isset( $this->settings_helper->settings[ $key . '_merchant_id' ] ) ) {
			/* translators: %s Merchant id */
			$description .= sprintf( __( '<br>Merchant id : "%s" ', 'alma-gateway-for-woocommerce' ), $this->settings_helper->settings[ $key . '_merchant_id' ] );
		}

		if ( isset( $this->settings_helper->settings[ $key . '_merchant_name' ] ) ) {
			/* translators: %s Merchant name */
			$description .= sprintf( __( '<br>Merchant name : "%s" ', 'alma-gateway-for-woocommerce' ), $this->settings_helper->settings[ $key . '_merchant_name' ] );
		}

		return $description;
	}

	/**
	 * Inits debug fields.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array
	 */
	public function init_debug_fields( $default_settings ) {
		$previous_version = get_option( 'alma_previous_version', 'N/A' );

		return array(
			'debug_section' => array(
				'title' => '<hr>' . __( '→ Debug options', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'debug'         => array(
				'title'       => __( 'Debug mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'checkbox',
				// translators: %s: Admin logs url.
				'label'       => __( 'Activate debug mode', 'alma-gateway-for-woocommerce' ) . sprintf( __( '(<a href="%s">Go to logs</a>)', 'alma-gateway-for-woocommerce' ), AssetsHelper::get_admin_logs_url() ),
				// translators: %s: The previous plugin version if exists.
				'description' => sprintf( __( 'Enable logging info and errors to help debug any issue with the plugin (previous Alma version : "%s")', 'alma-gateway-for-woocommerce' ), $previous_version ),
				'desc_tip'    => true,
				'default'     => $default_settings['debug'],
			),
		);
	}

	/**
	 * Inits display fields.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array
	 */
	public function init_inpage_fields( $default_settings ) {
			return array(
				'display_section'     => array(
					'title' => '<hr>' . __( '→ Display options', 'alma-gateway-for-woocommerce' ),
					'type'  => 'title',
				),
				'display_in_page'     => array(
					'title'   => __( 'Activate in-page checkout', 'alma-gateway-for-woocommerce' ),
					'type'    => 'checkbox',
					/* translators: %s: Alma in page doc URL */
					'label'   => __( 'Activate this setting if you want in-page checkout for Pay Now, Installment and Deferred payments.', 'alma-gateway-for-woocommerce' ) . '<br>' . sprintf( __( '(Learn more about this feature <a href="%s">here</a>)', 'alma-gateway-for-woocommerce' ), AssetsHelper::get_in_page_doc_link() ),
					'default' => $default_settings['display_in_page'],
				),
				'use_blocks_template' => array(
					'title'   => __( 'Activate compatibility with Blocks templates themes', 'alma-gateway-for-woocommerce' ),
					'type'    => 'checkbox',
					/* translators: %s: Woocommerce doc URL */
					'label'   => __( 'Activate this setting if you use a Blocks template Checkout page', 'alma-gateway-for-woocommerce' ) . '<br>' . sprintf( __( '(Learn more about this feature <a href="%s">here</a>)', 'alma-gateway-for-woocommerce' ), AssetsHelper::get_blocks_doc_link() ),
					'default' => $default_settings['use_blocks_template'],
				),
			);
	}

	/**
	 * Inits all allowed fee plans admin field.
	 *
	 * @param array $default_settings Default settings.
	 *
	 * @return array|array[]
	 */
	public function init_fee_plans_fields( $default_settings ) {
		$fee_plans_fields = array();
		$title_field      = array(
			'fee_plan_section' => array(
				'title' => '<hr>' . __( '→ Fee plans configuration', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
		);
		$select_options   = FormHtmlBuilder::generate_select_options( $this->settings_helper->allowed_fee_plans );

		uksort( $select_options, array( $this->fee_plan_helper, 'alma_usort_plans_keys' ) );

		if ( count( $select_options ) === 0 ) {
			/* translators: %s: Alma conditions URL */
			$title_field['fee_plan_section']['description'] = sprintf( __( '⚠ There is no fee plan allowed in your <a href="%s" target="_blank">Alma dashboard</a>.', 'alma-gateway-for-woocommerce' ), AssetsHelper::get_alma_dashboard_url( $this->settings_helper->get_environment(), 'conditions' ) );

			return $title_field;
		}

		$selected_fee_plan = $this->generate_selected_fee_plan_key( $select_options, $default_settings );

		foreach ( $this->settings_helper->allowed_fee_plans as $fee_plan ) {
			$fee_plans_fields = array_merge(
				$fee_plans_fields,
				$this->init_fee_plan_fields( $fee_plan, $default_settings, $selected_fee_plan === $fee_plan->getPlanKey() )
			);
		}

		return array_merge(
			$title_field,
			array(
				'selected_fee_plan' => array(
					'title'       => __( 'Select a fee plan to update', 'alma-gateway-for-woocommerce' ),
					'type'        => 'select_alma_fee_plan',
					/* translators: %s: Alma conditions URL */
					'description' => sprintf( __( 'Choose which fee plan you want to modify<br>(only your <a href="%s" target="_blank">Alma dashboard</a> available fee plans are shown here).', 'alma-gateway-for-woocommerce' ), AssetsHelper::get_alma_dashboard_url( $this->settings_helper->get_environment(), 'conditions' ) ),
					'default'     => $selected_fee_plan,
					'options'     => $select_options,
				),
			),
			$fee_plans_fields
		);
	}

	/**
	 * Generates the selected option for current fee_plan_keys options.
	 *
	 * @param array $select_options Key,value allowed fee_plan options.
	 * @param array $default_settings Default settings.
	 *
	 * @return string
	 */
	protected function generate_selected_fee_plan_key( array $select_options, $default_settings ) {
		$selected_fee_plan   = $this->settings_helper->selected_fee_plan ? $this->settings_helper->selected_fee_plan : $default_settings['selected_fee_plan'];
		$select_options_keys = array_keys( $select_options );

		return in_array( $selected_fee_plan, $select_options_keys, true ) ? $selected_fee_plan : $select_options_keys[0];
	}

	/**
	 * Inits a fee_plan's fields.
	 *
	 * @param FeePlan $fee_plan Fee plan definitions.
	 * @param array   $default_settings Default settings definitions.
	 * @param bool    $selected If this field is currently selected.
	 *
	 * @return array  as field_form definition
	 */
	protected function init_fee_plan_fields( FeePlan $fee_plan, $default_settings, $selected ) {
		$key                   = $fee_plan->getPlanKey();
		$min_amount_key        = 'min_amount_' . $key;
		$section_key           = $key . '_section';
		$max_amount_key        = 'max_amount_' . $key;
		$toggle_key            = 'enabled_' . $key;
		$class                 = 'alma_fee_plan alma_fee_plan_' . $key;
		$css                   = $selected ? '' : 'display: none;';
		$default_min_amount    = $this->tools_helper->alma_price_from_cents( $this->fee_plan_helper->get_min_purchase_amount( $fee_plan ) );
		$default_max_amount    = $this->tools_helper->alma_price_from_cents( $fee_plan->max_purchase_amount );
		$merchant_fee_fixed    = $this->tools_helper->alma_price_from_cents( $fee_plan->merchant_fee_fixed );
		$merchant_fee_variable = $fee_plan->merchant_fee_variable / 100; // percent.
		$customer_fee_fixed    = $this->tools_helper->alma_price_from_cents( $fee_plan->customer_fee_fixed );
		$customer_fee_variable = $fee_plan->customer_fee_variable / 100; // percent.
		$customer_lending_rate = $fee_plan->customer_lending_rate / 100; // percent.
		$default_enabled       = $default_settings['selected_fee_plan'] === $key ? 'yes' : 'no';
		$custom_attributes     = array(
			'required' => 'required',
			'min'      => $default_min_amount,
			'max'      => $default_max_amount,
			'step'     => 0.01,
		);

		$section_title = '';
		$toggle_label  = '';

		if ( $fee_plan->isPayNow() ) {
			$section_title = __( '→ Pay Now', 'alma-gateway-for-woocommerce' );
			// translators: %d: number of installments.
			$toggle_label = sprintf( __( 'Enable %d-installment payments with Alma', 'alma-gateway-for-woocommerce' ), $fee_plan->getInstallmentsCount() );
		}

		if ( $fee_plan->isPnXOnly() ) {
			// translators: %d: number of installments.
			$section_title = sprintf( __( '→ %d-installment payment', 'alma-gateway-for-woocommerce' ), $fee_plan->getInstallmentsCount() );
			// translators: %d: number of installments.
			$toggle_label = sprintf( __( 'Enable %d-installment payments with Alma', 'alma-gateway-for-woocommerce' ), $fee_plan->getInstallmentsCount() );
		}

		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_days   = $fee_plan->getDeferredDays();
			$deferred_months = $fee_plan->getDeferredMonths();
			if ( $deferred_days ) {
				// translators: %d: number of deferred days.
				$section_title = sprintf( __( '→ D+%d-deferred payment', 'alma-gateway-for-woocommerce' ), $deferred_days );
				// translators: %d: number of deferred days.
				$toggle_label = sprintf( __( 'Enable D+%d-deferred payments with Alma', 'alma-gateway-for-woocommerce' ), $deferred_days );
			}
			if ( $deferred_months ) {
				// translators: %d: number of deferred months.
				$section_title = sprintf( __( '→ M+%d-deferred payment', 'alma-gateway-for-woocommerce' ), $deferred_months );
				// translators: %d: number of deferred months.
				$toggle_label = sprintf( __( 'Enable M+%d-deferred payments with Alma', 'alma-gateway-for-woocommerce' ), $deferred_months );
			}
		}

		return array(
			$section_key    => array(
				'title'             => $section_title,
				'type'              => 'title',
				'description'       => $this->generate_fee_plan_description( $fee_plan, $default_min_amount, $default_max_amount, $merchant_fee_fixed, $merchant_fee_variable, $customer_fee_fixed, $customer_fee_variable, $customer_lending_rate ),
				'class'             => $class,
				'description_class' => $class,
				'table_class'       => $class,
				'css'               => $css,
				'description_css'   => $css,
				'table_css'         => $css,
			),
			$toggle_key     => array(
				'title'   => __( 'Enable/Disable', 'alma-gateway-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => $toggle_label,
				'default' => $default_enabled,
			),
			$min_amount_key => array(
				'title'             => __( 'Minimum amount', 'alma-gateway-for-woocommerce' ),
				'type'              => 'number',
				'css'               => 'width: 100px;',
				'custom_attributes' => $custom_attributes,
				'default'           => $default_min_amount,
			),
			$max_amount_key => array(
				'title'             => __( 'Maximum amount', 'alma-gateway-for-woocommerce' ),
				'type'              => 'number',
				'css'               => 'width: 100px;',
				'custom_attributes' => $custom_attributes,
				'default'           => $default_max_amount,
			),
		);
	}

	/**
	 * Gets fee plan description.
	 *
	 * @param FeePlan $fee_plan The fee plan do describe.
	 * @param float   $min_amount Min amount.
	 * @param float   $max_amount Max amount.
	 * @param float   $merchant_fee_fixed Merchant fee fixed.
	 * @param float   $merchant_fee_variable Merchant fee variable.
	 * @param float   $customer_fee_fixed Customer fee fixed.
	 * @param float   $customer_fee_variable Customer fee variable.
	 * @param float   $customer_lending_rate Customer lending rate.
	 *
	 * @return string
	 */
	protected function generate_fee_plan_description(
		FeePlan $fee_plan,
				$min_amount,
				$max_amount,
				$merchant_fee_fixed,
				$merchant_fee_variable,
				$customer_fee_fixed,
				$customer_fee_variable,
				$customer_lending_rate
	) {
		$you_can_offer = '';
		if ( $fee_plan->isPnXOnly() ) {
			$you_can_offer = sprintf(
			// translators: %d: number of installments.
				__( 'You can offer %1$d-installment payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-gateway-for-woocommerce' ),
				$fee_plan->installments_count,
				$min_amount,
				$max_amount
			);
		}

		if ( $fee_plan->isPayNow() ) {
			$you_can_offer = sprintf(
			// translators: %d: number of installments.
				__( 'You can offer instant payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-gateway-for-woocommerce' ),
				$fee_plan->installments_count,
				$min_amount,
				$max_amount
			);
		}

		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_days   = $fee_plan->getDeferredDays();
			$deferred_months = $fee_plan->getDeferredMonths();
			if ( $deferred_days ) {
				$you_can_offer = sprintf(
				// translators: %d: number of deferred days.
					__( 'You can offer D+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-gateway-for-woocommerce' ),
					$deferred_days,
					$min_amount,
					$max_amount
				);
			}
			if ( $deferred_months ) {
				$you_can_offer = sprintf(
				// translators: %d: number of deferred months.
					__( 'You can offer M+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-gateway-for-woocommerce' ),
					$deferred_months,
					$min_amount,
					$max_amount
				);
			}
		}
		$fees_applied  = __( 'Fees applied to each transaction for this plan:', 'alma-gateway-for-woocommerce' );
		$you_pay       = FormHtmlBuilder::generate_fee_to_pay_description(
			__( 'You pay:', 'alma-gateway-for-woocommerce' ),
			$merchant_fee_variable,
			$merchant_fee_fixed
		);
		$customer_pays = FormHtmlBuilder::generate_fee_to_pay_description(
			__( 'Customer pays:', 'alma-gateway-for-woocommerce' ),
			$customer_fee_variable,
			$customer_fee_fixed,
			// translators: %s Link to alma dashboard.
			'<br>' . sprintf( __( '<u>Note</u>: Customer fees are impacted by the usury rate, and will be adapted based on the limitations to comply with regulations. For more information, visit the Configuration page on your <a href="%s" target="_blank">Alma Dashboard</a>.', 'alma-gateway-for-woocommerce' ), AssetsHelper::get_alma_dashboard_url( $this->settings_helper->get_environment(), 'conditions' ) )
		);
		$customer_lending_pays = FormHtmlBuilder::generate_fee_to_pay_description(
			__( 'Customer lending rate:', 'alma-gateway-for-woocommerce' ),
			$customer_lending_rate,
			0
		);

		return sprintf( '<p>%s<br>%s %s %s %s</p>', $you_can_offer, $fees_applied, $you_pay, $customer_pays, $customer_lending_pays );
	}


	/**
	 * Inits default plugin fields.
	 *
	 * @param array $default_settings default settings.
	 *
	 * @return array
	 */
	public function init_general_settings_fields( array $default_settings ) {
		$fields_pay_now                     = array();
		$fields_pnx                         = array();
		$fields_pay_later                   = array();
		$fields_pnx_plus_4                  = array();
		$fields_in_page                     = array();
		$fields_in_page_pay_now             = array();
		$fields_in_page_pay_later           = array();
		$title_gateway_in_page              = array();
		$fields_title_gateway_in_page       = array();
		$fields_description_gateway_in_page = array();
		$fields_in_page_pnx_plus_4          = array();

		$general_settings_fields = array(
			'general_section' => array(
				'title' => '<hr>' . __( '→ General configuration', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'text_fields'     => array(
				'title' => FormHtmlBuilder::render_title( __( 'Edit the text displayed when choosing the payment method in your checkout.', 'alma-gateway-for-woocommerce' ) ),
				'type'  => 'title',
			),
		);

		if (
			isset( $this->settings_helper->settings['display_in_page'] )
			&& 'yes' === $this->settings_helper->settings['display_in_page']
		) {
			$fields_in_page = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID_IN_PAGE, __( 'Payments in 2, 3 and 4 installments:', 'alma-gateway-for-woocommerce' ), $default_settings );

			if ( $this->settings_helper->has_pay_now() ) {
				$fields_in_page_pay_now = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW, __( 'Pay Now:', 'alma-gateway-for-woocommerce' ), $default_settings );
			}
			if ( $this->settings_helper->has_pay_later() ) {
				$fields_in_page_pay_later = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER, __( 'Deferred Payments:', 'alma-gateway-for-woocommerce' ), $default_settings );
			}
			if ( $this->settings_helper->has_pnx_plus_4() ) {
				$fields_in_page_pnx_plus_4 = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR, __( 'Payments in more than 4 installments:', 'alma-gateway-for-woocommerce' ), $default_settings );
			}
		}

		if (
			isset( $this->settings_helper->settings['display_in_page'] )
			&& 'no' === $this->settings_helper->settings['display_in_page']
		) {
			$fields_pnx = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID, __( 'Payments in 2, 3 and 4 installments:', 'alma-gateway-for-woocommerce' ), $default_settings );

			if ( $this->settings_helper->has_pay_now() ) {
				$fields_pay_now = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID_PAY_NOW, __( 'Pay now:', 'alma-gateway-for-woocommerce' ), $default_settings );
			}

			if ( $this->settings_helper->has_pay_later() ) {
				$fields_pay_later = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID_PAY_LATER, __( 'Deferred Payments:', 'alma-gateway-for-woocommerce' ), $default_settings );
			}
		}

		if ( $this->settings_helper->has_pnx_plus_4() ) {
			$fields_pnx_plus_4 = $this->get_custom_fields_payment_method( ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR, __( 'Payments in more than 4 installments:', 'alma-gateway-for-woocommerce' ), $default_settings );
		}

		$general_settings_fields_end = array(
			'display_product_eligibility' => array(
				'title'   => __( 'Product eligibility notice', 'alma-gateway-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Display a message about product eligibility for monthly payments', 'alma-gateway-for-woocommerce' ),
				'default' => $default_settings['display_product_eligibility'],
			),
			'display_cart_eligibility'    => array(
				'title'   => __( 'Cart eligibility notice', 'alma-gateway-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Display a message about cart eligibility for monthly payments', 'alma-gateway-for-woocommerce' ),
				'default' => $default_settings['display_cart_eligibility'],
			),
			'excluded_products_list'      => array(
				'title'       => __( 'Excluded product categories', 'alma-gateway-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Exclude all virtual/downloadable product categories, as you cannot sell them with Alma', 'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
				'css'         => 'height: 150px;',
				'options'     => FormHtmlBuilder::generate_categories_options(),
			),
		);

		$field_cart_not_eligible_message_gift_cards = $this->internationalization_helper->generate_i18n_field(
			'cart_not_eligible_message_gift_cards',
			array(
				'title'       => __( 'Non-eligibility message for excluded products', 'alma-gateway-for-woocommerce' ),
				'description' => __( 'Message displayed below the cart totals when it contains excluded products', 'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
			),
			$default_settings['cart_not_eligible_message_gift_cards']
		);

		return array_merge(
			$general_settings_fields,
			$title_gateway_in_page,
			$fields_title_gateway_in_page,
			$fields_description_gateway_in_page,
			$fields_pay_now,
			$fields_in_page_pay_now,
			$fields_pnx,
			$fields_in_page,
			$fields_pay_later,
			$fields_in_page_pay_later,
			$fields_pnx_plus_4,
			$fields_in_page_pnx_plus_4,
			$general_settings_fields_end,
			$field_cart_not_eligible_message_gift_cards
		);
	}

	/**
	 * Gets custom fields for a payment method.
	 *
	 * @param string $payment_method_name The payment method name.
	 * @param string $title The title.
	 * @param array  $default_settings The defaults settings.
	 *
	 * @return array[]
	 */
	protected function get_custom_fields_payment_method( $payment_method_name, $title, array $default_settings ) {

		$blocks = '';

		if ( $this->block_helper->has_woocommerce_blocks() ) {
			$blocks = 'blocks_';
		}

		$fields = array(
			$payment_method_name => array(
				'title' => sprintf( '<h4 style="color:#777;font-size:1.15em;">%s</h4>', $title ),
				'type'  => 'title',
			),
		);

		$field_payment_method_title = $this->internationalization_helper->generate_i18n_field(
			'title_' . $blocks . $payment_method_name,
			array(
				'title'       => __( 'Title', 'alma-gateway-for-woocommerce' ),
				'description' => __( 'This controls the payment method name which the user sees during checkout.', 'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
			),
			$default_settings[ 'title_' . $blocks . $payment_method_name ]
		);

		$field_payment_method_description = $this->internationalization_helper->generate_i18n_field(
			'description_' . $blocks . $payment_method_name,
			array(
				'title'       => __( 'Description', 'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'This controls the payment method description which the user sees during checkout.', 'alma-gateway-for-woocommerce' ),
			),
			$default_settings[ 'description_' . $blocks . $payment_method_name ]
		);

		return array_merge(
			$fields,
			$field_payment_method_title,
			$field_payment_method_description
		);
	}

	/**
	 * The upon trigger fields.
	 *
	 * @param array $default_settings The default settings.
	 *
	 * @return array[]
	 */
	public function init_payment_upon_trigger_fields( $default_settings ) {

		$title_field = array(
			'payment_upon_trigger_section' => array(
				'title' => '<hr>' . __( '→ Payment upon trigger configuration', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
		);

		if ( ! $this->payment_upon_trigger->has_merchant_payment_upon_trigger_enabled() ) {
			return array();
		}

		return array_merge(
			$title_field,
			array(
				'payment_upon_trigger_general_info' => array(
					'title' => FormHtmlBuilder::render_title(
						__( 'This option is available only for Alma payment in 2x, 3x and 4x.<br>When it\'s turned on, your clients will pay the first installment at the order status change. When your client order on your website, Alma will only ask for a payment authorization. Only status handled by Alma are available in the menu below. Please contact Alma if you need us to add another status.', 'alma-gateway-for-woocommerce' )
					),
					'type'  => 'title',
				),
				'payment_upon_trigger_enabled'      => array(
					'title'   => __( 'Activate the payment upon trigger', 'alma-gateway-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => '&nbsp;',
					'default' => $default_settings['enabled'],
				),
				'payment_upon_trigger_display_text' => array(
					'type'        => 'select',
					'title'       => __( 'Trigger typology', 'alma-gateway-for-woocommerce' ),
					'description' => __( 'Text that will appear in the payments schedule and in the customer\'s payment authorization email.', 'alma-gateway-for-woocommerce' ),
					'default'     => $default_settings['payment_upon_trigger_display_text'],
					'options'     => $this->internationalization_helper->get_display_texts_keys_and_values(),
				),
				'payment_upon_trigger_event'        => array(
					'type'    => 'select',
					'title'   => __( 'Order status that triggers the first payment', 'alma-gateway-for-woocommerce' ),
					'default' => $default_settings['payment_upon_trigger_event'],
					'options' => $this->payment_upon_trigger->get_order_statuses(),
				),
			)
		);
	}

	/**
	 * Technical fields.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array[]
	 */
	public function init_technical_fields( $default_settings ) {

		return array(
			'technical_section'                          => array(
				'title'       => '<hr>' . __( '→ Technical fields', 'alma-gateway-for-woocommerce' ),
				'type'        => 'title',
				'description' => __( 'Specific fields just in case you need it. [<a href="#" id="alma_link_toggle_technical_section">click to open or close</a>]', 'alma-gateway-for-woocommerce' ),
			),
			'variable_product_check_variations_event'    => array(
				'title'       => __( 'Custom check variations event', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => sprintf(
				// translators: %1$s is technical information, %2$s is Alma WooCommerce Plugin FAQ doc URL.
					__( 'This is the javascript event triggered on variables products page, when the customer change the product variation. Default value is <strong>%1$s</strong>.<br />More technical information on <a href="%2$s" target="_blank">Alma documentation</a>', 'alma-gateway-for-woocommerce' ),
					ConstantsHelper::DEFAULT_CHECK_VARIATIONS_EVENT,
					'https://docs.almapay.com/docs/woocommerce-faq'
				),
				'default'     => $default_settings['variable_product_check_variations_event'],
			),
			'variable_product_price_query_selector'      => array(
				'title'       => __( 'Variable products price query selector', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => sprintf(
				// translators: %s is technical information.
					__( 'Query selector used to get the price of product with variations. Default value is <strong>%s</strong>.', 'alma-gateway-for-woocommerce' ),
					$default_settings['variable_product_price_query_selector']
				),
				'default'     => $default_settings['variable_product_price_query_selector'],
			),
			'variable_product_sale_price_query_selector' => array(
				'title'       => __( 'Variable products sale price query selector', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => sprintf(
				// translators: %s is technical information.
					__( 'Query selector used to get the price of product with <strong>sales variations</strong>. Default value is <strong>%s</strong>.', 'alma-gateway-for-woocommerce' ),
					$default_settings['variable_product_sale_price_query_selector']
				),
				'default'     => $default_settings['title_alma'],
			),
		);
	}

	/**
	 * Inits share of checkout Admin field.
	 *
	 * @param array $default_settings default settings.
	 *
	 * @return array[]
	 */
	public function init_share_of_checkout_field( $default_settings ) {
		if (
			empty( $this->settings_helper->__get( 'share_of_checkout_enabled_date' ) )
			|| $this->settings_helper->is_test()
		) {
			return array();
		}

		return array(
			'share_of_checkout_section' => array(
				'title'       => '<hr>' . __( '→ Increase your performance & get insights !', 'alma-gateway-for-woocommerce' ),
				'type'        => 'title',
				'description' => wp_kses_post(
					__( 'By accepting this option, enable Alma to analyse the usage of your payment methods, get more informations to perform and share this data with you.', 'alma-gateway-for-woocommerce' ) .
					__( '<br>You can <a href="mailto:support@getalma.eu">erase your data</a> at any moment.', 'alma-gateway-for-woocommerce' ) .
					'<p class="alma-legal-checkout-collapsible">' .
					__( 'Know more about collected data', 'alma-gateway-for-woocommerce' ) .
					'<span id="alma-legal-collapse-chevron" class="alma-legal-checkout-chevron bottom"></span>' .
					'</p>' .
					'<ul class="alma-legal-checkout-content"><li>' .
					__( '- total quantity of orders, amounts and currencies', 'alma-gateway-for-woocommerce' ) .
					'</li><li>' .
					__( '- payment provider for each order', 'alma-gateway-for-woocommerce' ) .
					'</li></ul>'
				),
			),

			'share_of_checkout_enabled' => array(
				'title'   => __( 'Activate your data sharing settings ', 'alma-gateway-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => '&nbsp;',
				'default' => $default_settings['share_of_checkout_enabled'],
			),
		);
	}
}
