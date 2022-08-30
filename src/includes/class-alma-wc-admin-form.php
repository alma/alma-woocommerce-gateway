<?php
/**
 * Alma WooCommerce payment gateway
 *
 * @package Alma_WooCommerce_Gateway
 * @noinspection HtmlUnknownTarget
 */

use Alma\API\Entities\FeePlan;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Admin_Form
 */
class Alma_WC_Admin_Form {

	/**
	 * Singleton static property.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Admin Form fields initialization.
	 *
	 * @return array
	 */
	public static function init_form_fields() {
		//do_action('show_share_checkout');
		$need_api_key     = alma_wc_plugin()->settings->need_api_key();
		$default_settings = Alma_WC_Settings::default_settings();

		if ( $need_api_key ) {
			return array_merge(
				self::get_instance()->init_enabled_field( $default_settings ),
				self::get_instance()->init_api_key_fields( __( '→ Start by filling in your API keys', 'alma-gateway-for-woocommerce' ), $default_settings ),
				self::get_instance()->init_debug_fields( $default_settings )
			);
		}

		return array_merge(
			self::get_instance()->init_enabled_field( $default_settings ),
			self::get_instance()->init_fee_plans_fields( $default_settings ),
			self::get_instance()->init_general_settings_fields( $default_settings ),
			self::get_instance()->init_payment_upon_trigger_fields( $default_settings ),
			self::get_instance()->init_api_key_fields( __( '→ API configuration', 'alma-gateway-for-woocommerce' ), $default_settings ),
			self::get_instance()->init_share_of_checkout_field( $default_settings ),
			self::get_instance()->init_technical_fields( $default_settings ),
			self::get_instance()->init_debug_fields( $default_settings )
		);
	}

	/**
	 * Inits Payment upon trigger fields.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array[]
	 */
	private function init_payment_upon_trigger_fields( $default_settings ) {

		$title_field = array(
			'payment_upon_trigger_section' => array(
				'title' => '<hr>' . __( '→ Payment upon trigger configuration', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
		);

		if ( ! Alma_WC_Payment_Upon_Trigger::has_merchant_payment_upon_trigger_enabled() ) {
			return array_merge(
				$title_field,
				array(
					'payment_upon_trigger_enabling_info' => array(
						// translators: %1$s: Alma contact email.
						'title' => $this->render_title( sprintf( __( 'If you are interested in this feature, please get closer to your Alma contact or by sending an email to <a href="mailto:%1$s">%1$s</a>', 'alma-gateway-for-woocommerce' ), 'support@getalma.eu' ) ),
						'type'  => 'title',
					),
				)
			);
		}

		return array_merge(
			$title_field,
			array(
				'payment_upon_trigger_general_info' => array(
					'title' => $this->render_title( __( 'This option is available only for Alma payment in 2x, 3x and 4x.<br>When it\'s turned on, your clients will pay the first installment at the order status change. When your client order on your website, Alma will only ask for a payment authorization. Only status handled by Alma are available in the menu below. Please contact Alma if you need us to add another status.', 'alma-gateway-for-woocommerce' ) ),
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
					'options'     => Alma_WC_Payment_Upon_Trigger::get_display_texts_keys_and_values(),
				),
				'payment_upon_trigger_event'        => array(
					'type'    => 'select',
					'title'   => __( 'Order status that triggers the first payment', 'alma-gateway-for-woocommerce' ),
					'default' => $default_settings['payment_upon_trigger_event'],
					'options' => Alma_WC_Payment_Upon_Trigger::get_order_statuses(),
				),
			)
		);
	}

	/**
	 * Render "title" field type with some special css.
	 *
	 * @param string $title The title text to display.
	 * @return string
	 */
	private function render_title( $title ) {
		return '<p style="font-weight:normal;">' . $title . '</p>';
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
	private function init_fee_plan_fields( FeePlan $fee_plan, $default_settings, $selected ) {
		$key                   = $fee_plan->getPlanKey();
		$min_amount_key        = 'min_amount_' . $key;
		$section_key           = $key . '_section';
		$max_amount_key        = 'max_amount_' . $key;
		$toggle_key            = 'enabled_' . $key;
		$class                 = 'alma_fee_plan alma_fee_plan_' . $key;
		$css                   = $selected ? '' : 'display: none;';
		$default_min_amount    = alma_wc_price_from_cents( $fee_plan->min_purchase_amount );
		$default_max_amount    = alma_wc_price_from_cents( $fee_plan->max_purchase_amount );
		$merchant_fee_fixed    = alma_wc_price_from_cents( $fee_plan->merchant_fee_fixed );
		$merchant_fee_variable = $fee_plan->merchant_fee_variable / 100; // percent.
		$customer_fee_fixed    = alma_wc_price_from_cents( $fee_plan->customer_fee_fixed );
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
	 * Inits enabled Admin field.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array[]
	 */
	private function init_enabled_field( $default_settings ) {
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
	 * Inits share of checkout Admin field.
	 *
	 * @param array $default_settings default settings.
	 *
	 * @return array[]
	 */
	private function init_share_of_checkout_field( $default_settings ) {

		return array(
			'share_of_checkout_section'      => array(
				'title' => '<hr>' . __( '→ Share of checkout configuration', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'share_of_checkout_general_info' => array(
				'title' => $this->render_title(
					__(
						'Hello,<br>In order to improve the performance monitoring of Alma on your site, we plan to develop an automated statistical data export from our module.<br>The following data would be collected periodically:<br>
	- the version of your CMS and the Alma module used.<br>
	- the activation status of the Alma badge.<br>
	- The share of Alma transactions among your different payment methods (in value and volume).<br>
	This data would allow us, on the one hand, to better support you to strengthen your conversion and, on the other hand, to monitor the impact of the improvements made to the solution.<br>
	No data relating to the details of orders or personal data of your customers would be exported. All data collected will be processed in compliance with the GDPR.<br>
	In addition, the option can be fully deactivated from the administration of your CMS; the data being collected only with your consent.<br>
	In order to optimize the use of Alma on your site, would you agree, in principle, to sharing this statistical data with us?<br>
	The Alma team.',
						'alma-gateway-for-woocommerce'
					)
				),
				'type'  => 'title',
			),
			'share_of_checkout_enabled'      => array(
				'title'   => __( 'Activate the share of checkout', 'alma-gateway-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => '&nbsp;',
				'default' => $default_settings['share_of_checkout_enabled'],
			),
		);
	}

	/**
	 * Technical fields.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array[]
	 */
	private function init_technical_fields( $default_settings ) {

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
					Alma_WC_Settings::DEFAULT_CHECK_VARIATIONS_EVENT,
					'https://docs.getalma.eu/docs/woocommerce-faq'
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
				'default'     => $default_settings['title_payment_method_pnx'],
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
	private function init_api_key_fields( $keys_title, $default_settings ) {

		return array(
			'keys_section' => array(
				'title'       => '<hr>' . $keys_title,
				'type'        => 'title',
				/* translators: %s Alma security URL */
				'description' => sprintf( __( 'You can find your API keys on <a href="%s" target="_blank">your Alma dashboard</a>', 'alma-gateway-for-woocommerce' ), alma_wc_plugin()->get_alma_dashboard_url( 'security' ) ),
			),
			'live_api_key' => array(
				'title' => __( 'Live API key', 'alma-gateway-for-woocommerce' ),
				'type'  => 'text',
			),
			'test_api_key' => array(
				'title' => __( 'Test API key', 'alma-gateway-for-woocommerce' ),
				'type'  => 'text',
			),
			'environment'  => array(
				'title'       => __( 'API Mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.', 'alma-gateway-for-woocommerce' ),
				'default'     => $default_settings['environment'],
				'options'     => array(
					'test' => __( 'Test', 'alma-gateway-for-woocommerce' ),
					'live' => __( 'Live', 'alma-gateway-for-woocommerce' ),
				),
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
	private function init_fee_plans_fields( $default_settings ) {
		$fee_plans_fields = array();
		$title_field      = array(
			'fee_plan_section' => array(
				'title' => '<hr>' . __( '→ Fee plans configuration', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
		);
		$select_options   = $this->generate_select_options();
		if ( count( $select_options ) === 0 ) {
			/* translators: %s: Alma conditions URL */
			$title_field['fee_plan_section']['description'] = sprintf( __( '⚠ There is no fee plan allowed in your <a href="%s" target="_blank">Alma dashboard</a>.', 'alma-gateway-for-woocommerce' ), alma_wc_plugin()->get_alma_dashboard_url( 'conditions' ) );

			return $title_field;
		}
		$selected_fee_plan = $this->generate_selected_fee_plan_key( $select_options, $default_settings );
		foreach ( alma_wc_plugin()->settings->get_allowed_fee_plans() as $fee_plan ) {
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
					'description' => sprintf( __( 'Choose which fee plan you want to modify<br>(only your <a href="%s" target="_blank">Alma dashboard</a> available fee plans are shown here).', 'alma-gateway-for-woocommerce' ), alma_wc_plugin()->get_alma_dashboard_url( 'conditions' ) ),
					'default'     => $selected_fee_plan,
					'options'     => $select_options,
				),
			),
			$fee_plans_fields
		);
	}

	/**
	 * Inits default plugin fields.
	 *
	 * @param array $default_settings default settings.
	 *
	 * @return array
	 */
	private function init_general_settings_fields( array $default_settings ) {
		$general_settings_fields = array(
			'general_section' => array(
				'title' => '<hr>' . __( '→ General configuration', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'text_fields'     => array(
				'title' => $this->render_title( __( 'Edit the text displayed when choosing the payment method in your checkout.', 'alma-gateway-for-woocommerce' ) ),
				'type'  => 'title',
			),
		);

		$fields_pnx = $this->get_custom_fields_payment_method( 'payment_method_pnx', __( 'Payments in 2, 3 and 4 installments:', 'alma-gateway-for-woocommerce' ), $default_settings );

		$fields_pay_later = array();
		if ( alma_wc_plugin()->settings->has_pay_later() ) {
			$fields_pay_later = $this->get_custom_fields_payment_method( 'payment_method_pay_later', __( 'Deferred Payments:', 'alma-gateway-for-woocommerce' ), $default_settings );
		}

		$fields_pnx_plus_4 = array();
		if ( alma_wc_plugin()->settings->has_pnx_plus_4() ) {
			$fields_pnx_plus_4 = $this->get_custom_fields_payment_method( 'payment_method_pnx_plus_4', __( 'Payments in more than 4 installments:', 'alma-gateway-for-woocommerce' ), $default_settings );
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
				'options'     => $this->generate_categories_options(),
			),
		);

		$field_cart_not_eligible_message_gift_cards = $this->generate_i18n_field(
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
			$fields_pnx,
			$fields_pay_later,
			$fields_pnx_plus_4,
			$general_settings_fields_end,
			$field_cart_not_eligible_message_gift_cards
		);
	}

	/**
	 * Adds all the translated fields for one field.
	 *
	 * @param string $field_name The field name.
	 * @param array  $field_infos The information for this field.
	 * @param string $default The default value for the field.
	 *
	 * @return array
	 */
	private function generate_i18n_field( $field_name, $field_infos, $default ) {
		if ( Alma_WC_Internationalization::is_site_multilingual() ) {
			$new_fields = array();
			$lang_list  = Alma_WC_Internationalization::get_list_languages();
			foreach ( $lang_list as $code_lang => $label_lang ) {
				$new_file_key                 = $field_name . '_' . $code_lang;
				$new_field_infos              = $field_infos;
				$new_field_infos['type']      = 'text_alma_i18n';
				$new_field_infos['class']     = $code_lang;
				$new_field_infos['default']   = Alma_WC_Internationalization::get_translated_text( $default, $code_lang );
				$new_field_infos['lang_list'] = $lang_list;

				$new_fields[ $new_file_key ] = $new_field_infos;
			}

			return $new_fields;
		}

		$additional_infos = array(
			'type'    => 'text',
			'class'   => 'alma-i18n',
			'default' => $default,
		);
		return array( $field_name => array_merge( $field_infos, $additional_infos ) );
	}

	/**
	 * Inits debug fields.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array
	 */
	private function init_debug_fields( $default_settings ) {
		return array(
			'debug_section' => array(
				'title' => '<hr>' . __( '→ Debug options', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'debug'         => array(
				'title'       => __( 'Debug mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'checkbox',
				// translators: %s: Admin logs url.
				'label'       => __( 'Activate debug mode', 'alma-gateway-for-woocommerce' ) . sprintf( __( '(<a href="%s">Go to logs</a>)', 'alma-gateway-for-woocommerce' ), alma_wc_plugin()->get_admin_logs_url() ),
				'description' => __( 'Enable logging info and errors to help debug any issue with the plugin', 'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => $default_settings['debug'],
			),
		);
	}

	/**
	 * Singleton static method.
	 *
	 * @return Alma_WC_Admin_Form
	 */
	private static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Product categories options.
	 *
	 * @return array
	 */
	private function generate_categories_options() {
		$product_categories = get_terms(
			'product_cat',
			array(
				'orderby'    => 'name',
				'order'      => 'asc',
				'hide_empty' => false,
			)
		);

		$options = array();
		foreach ( $product_categories as $category ) {
			$options[ $category->slug ] = $category->name;
		}

		return $options;
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
	private function generate_fee_plan_description(
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
		$fees_applied          = __( 'Fees applied to each transaction for this plan:', 'alma-gateway-for-woocommerce' );
		$you_pay               = $this->generate_fee_to_pay_description( __( 'You pay:', 'alma-gateway-for-woocommerce' ), $merchant_fee_variable, $merchant_fee_fixed );
		$customer_pays         = $this->generate_fee_to_pay_description( __( 'Customer pays:', 'alma-gateway-for-woocommerce' ), $customer_fee_variable, $customer_fee_fixed );
		$customer_lending_pays = $this->generate_fee_to_pay_description( __( 'Customer lending rate:', 'alma-gateway-for-woocommerce' ), $customer_lending_rate, 0 );

		return sprintf( '<p>%s<br>%s %s %s %s</p>', $you_can_offer, $fees_applied, $you_pay, $customer_pays, $customer_lending_pays );
	}

	/**
	 * Generates a string with % + € OR only % OR only € (depending on parameters given).
	 * If all fees are <= 0 : return an empty string.
	 *
	 * @param string $translation as description prefix.
	 * @param float  $fee_variable as variable amount (if any).
	 * @param float  $fee_fixed as fixed amount (if any).
	 *
	 * @return string
	 */
	private function generate_fee_to_pay_description( $translation, $fee_variable, $fee_fixed ) {
		if ( ! $fee_variable && ! $fee_fixed ) {
			return '';
		}

		$fees = '';
		if ( $fee_variable ) {
			$fees .= $fee_variable . '%';
		}

		if ( $fee_fixed ) {
			if ( $fee_variable ) {
				$fees .= ' + ';
			}
			$fees .= $fee_fixed . '€';
		}

		return sprintf( '<br><b>%s</b> %s', $translation, $fees );
	}

	/**
	 * Generates select options key values for allowed fee_plans.
	 *
	 * @return array
	 */
	private function generate_select_options() {
		$select_options = array();
		foreach ( alma_wc_plugin()->settings->get_allowed_fee_plans() as $fee_plan ) {
			$select_label = '';
			if ( $fee_plan->isPnXOnly() ) {
				// translators: %d: number of installments.
				$select_label = sprintf( __( '→ %d-installment payment', 'alma-gateway-for-woocommerce' ), $fee_plan->getInstallmentsCount() );
			}
			if ( $fee_plan->isPayLaterOnly() ) {
				$deferred_months = $fee_plan->getDeferredMonths();
				$deferred_days   = $fee_plan->getDeferredDays();
				if ( $deferred_days ) {
					// translators: %d: number of deferred days.
					$select_label = sprintf( __( '→ D+%d-deferred payment', 'alma-gateway-for-woocommerce' ), $deferred_days );
				}
				if ( $deferred_months ) {
					// translators: %d: number of deferred months.
					$select_label = sprintf( __( '→ M+%d-deferred payment', 'alma-gateway-for-woocommerce' ), $deferred_months );
				}
			}
			$select_options[ $fee_plan->getPlanKey() ] = $select_label;
		}

		return $select_options;
	}

	/**
	 * Generates the selected option for current fee_plan_keys options.
	 *
	 * @param array $select_options Key,value allowed fee_plan options.
	 * @param array $default_settings Default settings.
	 *
	 * @return string
	 */
	private function generate_selected_fee_plan_key( array $select_options, $default_settings ) {
		$selected_fee_plan   = alma_wc_plugin()->settings->selected_fee_plan ? alma_wc_plugin()->settings->selected_fee_plan : $default_settings['selected_fee_plan'];
		$select_options_keys = array_keys( $select_options );

		return in_array( $selected_fee_plan, $select_options_keys, true ) ? $selected_fee_plan : $select_options_keys[0];
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
	private function get_custom_fields_payment_method( $payment_method_name, $title, array $default_settings ) {

		$fields = array(
			$payment_method_name => array(
				'title' => sprintf( '<h4 style="color:#777;font-size:1.15em;">%s</h4>', $title ),
				'type'  => 'title',
			),
		);

		$field_payment_method_title = $this->generate_i18n_field(
			'title_' . $payment_method_name,
			array(
				'title'       => __( 'Title', 'alma-gateway-for-woocommerce' ),
				'description' => __( 'This controls the payment method name which the user sees during checkout.', 'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
			),
			$default_settings[ 'title_' . $payment_method_name ]
		);

		$field_payment_method_description = $this->generate_i18n_field(
			'description_' . $payment_method_name,
			array(
				'title'       => __( 'Description', 'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'This controls the payment method description which the user sees during checkout.', 'alma-gateway-for-woocommerce' ),
			),
			$default_settings[ 'description_' . $payment_method_name ]
		);

		return array_merge(
			$fields,
			$field_payment_method_title,
			$field_payment_method_description
		);
	}

}

