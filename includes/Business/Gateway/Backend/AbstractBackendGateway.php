<?php

namespace Alma\Gateway\Business\Gateway\Backend;

use Alma\API\Entities\FeePlan;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Business\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Business\Helper\AssetsHelper;
use Alma\Gateway\Business\Helper\DisplayHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\Business\Service\OptionsService;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;

class AbstractBackendGateway extends AbstractGateway {

	public const FIELD_LIVE_API_KEY = 'live_api_key';
	public const FIELD_TEST_API_KEY = 'test_api_key';

	public const MIN_AMOUNT_SUFFIX = 'min_amount';

	public const MAX_AMOUNT_SUFFIX = 'max_amount';

	/**
	 * Generate HTML components for the settings form fields.
	 *
	 * Override to allow to generate the HTML with decorators.
	 *
	 * @param array $form_fields
	 * @param true  $echo Not used, but kept for compatibility.
	 *
	 * @throws ContainerException
	 * @phpcs Not used, but kept for compatibility.
	 */
	public function generate_settings_html( $form_fields = array(), $echo = true ): string {// phpcs:ignore

		if ( empty( $form_fields ) ) {
			$form_fields = $this->get_form_fields();
		}

		$html = '';
		foreach ( $form_fields as $key => $value ) {
			$type = $this->get_field_type( $value );

			if ( method_exists( $this, 'generate_' . $type . '_html' ) ) {
				$html .= $this->decorate(
					$this->{'generate_' . $type . '_html'}( $key, $value ),
					$this->get_field_decorator( $value )
				);
			} else {
				$html .= $this->generate_text_html( $key, $value );
			}
		}

		return $html;
	}

	/**
	 * Get a field decorator. Defaults to empty decorator if not set.
	 * A decorator is a string that contains a placeholder `%s` to be replaced by the field HTML component.
	 *
	 * @param array $field Field key.
	 *
	 * @return string
	 */
	public function get_field_decorator( array $field ): string {
		if ( ! empty( $field['decorator'] )
			&& is_string( $field['decorator'] )
			&& strpos( $field['decorator'], '%s' ) !== false
		) {
			return $field['decorator'];
		}

		return '%s';
	}

	public function decorate( string $component, string $decorator ): string {
		return sprintf( $decorator, $component );
	}


	/**
	 * Inits enabled Admin field.
	 *
	 * @return array[]
	 */
	public function enabled_field(): array {

		return array(
			'enabled' => array(
				'title'    => L10nHelper::__( 'Enable/Disable' ),
				'type'     => 'checkbox',
				'label'    => L10nHelper::__( 'Enable monthly payments with Alma' ),
				'default'  => 'yes',
				'desc_tip' => false,
			),
		);
	}

	public function api_key_fieldset(): array {

		return array(
			'keys_section'           => array(
				'title'       => '<hr>' . L10nHelper::__( '→ Start by filling in your API keys' ),
				'type'        => 'title',
				'description' => L10nHelper::__( 'You can find your API keys on your Alma dashboard</a>' ),
				'desc_tip'    => false,
			),
			self::FIELD_LIVE_API_KEY => array(
				'title'    => L10nHelper::__( 'Live API key' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			self::FIELD_TEST_API_KEY => array(
				'title'    => L10nHelper::__( 'Test API key' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'environment'            => array(
				'title'       => L10nHelper::__( 'API Mode' ),
				'type'        => 'select',
				/* translators: %s Merchant description */
				'description' => sprintf(
					L10nHelper::__( 'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.<br> %s' ),
					'TODO: Informations marchand'
				),
				'desc_tip'    => true,
				'default'     => 'test',
				'options'     => array(
					'test' => L10nHelper::__( 'Test' ),
					'live' => L10nHelper::__( 'Live' ),
				),
				'class'       => 'wc-enhanced-select',
			),
		);
	}

	/**
	 * Define the fee plan section.
	 *
	 * @throws ContainerException
	 * @throws MerchantServiceException
	 */
	public function fee_plan_fieldset(): array {

		// Get the default fee plans.
		/** @var FeePlanService $fee_plan_service */
		$fee_plan_service = Plugin::get_container()->get( FeePlanService::class );
		$fee_plan_list    = $fee_plan_service->get_fee_plan_list( true );

		$field_list['fee_plan_section'] = array(
			'title'    => '<hr>' . L10nHelper::__( '→ Fee plans configuration' ),
			'type'     => 'title',
			'desc_tip' => false,
		);
		/** @see self::generate_custom_html() */
		$field_list['fee_plan_header'] = array(
			'type'     => 'custom',
			'html'     => <<<HTML
			<tr valign="top">
				<table class="form-table">
					<tr valign="top"><td class="wc_emails_wrapper" colspan="2">
						<table class="wc_gateways widefat" cellspacing="0">
							<thead>
								<tr>
									<th class="wc-gateway-settings-table-name">Payment method</th>
									<th class="wc-gateway-settings-table-name">Status</th>
									<th class="wc-gateway-settings-table-name">Description</th>
									<th class="wc-gateway-settings-table-name">Min amount</th>
									<th class="wc-gateway-settings-table-name">Max amount</th>
								</tr>
							</thead>
							<tbody class="ui-sortable">
			HTML,
			'desc_tip' => false,
		);

		/** @var FeePlan $fee_plan */
		foreach ( $fee_plan_list as $fee_plan ) {
			/** @see self::generate_table_title_html() */
			$field_list[ $fee_plan->getPlanKey() . '_title' ] = array(
				'type'      => 'table_title',
				'title'     => $this->generate_fee_plan_title( $fee_plan ),
				'decorator' => '<tr data-fee_plan_key="' . $fee_plan->getPlanKey() . '">%s',
				'desc_tip'  => false,
			);
			/**
			 * @see self::generate_table_toggle_html()
			 */
			$field_list[ $fee_plan->getPlanKey() ] = array(
				'type'        => 'table_toggle',
				'fee_plan'    => $fee_plan,
				'description' => 'Enable/Disable Fee Plan',
				'desc_tip'    => true,
				'enabled'     => $fee_plan->isEnabled(),
			);
			/** @see self::generate_table_description_html() */
			$field_list[ $fee_plan->getPlanKey() . '_description' ] = array(
				'type'        => 'table_description',
				'description' => $this->generate_fee_plan_description( $fee_plan ),
				'desc_tip'    => true,
			);
			/** @see self::generate_table_min_amount_html() */
			$field_list[ $fee_plan->getPlanKey() . '_min_amount' ] = array(
				'type'        => 'table_min_amount',
				'description' => $this->generate_fee_plan_description( $fee_plan ),
				'desc_tip'    => true,
				'default'     => $fee_plan->getMinPurchaseAmount() / 100,
				'value'       => $fee_plan->getMinPurchaseAmount( true ) / 100,
			);
			/** @see self::generate_table_max_amount_html() */
			$field_list[ $fee_plan->getPlanKey() . '_max_amount' ] = array(
				'type'        => 'table_max_amount',
				'description' => $this->generate_fee_plan_description( $fee_plan ),
				'desc_tip'    => true,
				'default'     => $fee_plan->getMaxPurchaseAmount() / 100,
				'value'       => $fee_plan->getMaxPurchaseAmount( true ) / 100,
				'decorator'   => '%s</tr>',
			);
		}

		/** @see self::generate_custom_html() */
		$field_list['fee_plan_footer'] = array(
			'type'     => 'custom',
			'html'     => <<<HTML
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</tr>
			HTML,
			'desc_tip' => false,
		);

		return $field_list;
	}

	/**
	 * Define the gateway order section.
	 */
	public function gateway_order_fieldset(): array {

		$gateway_list = array(
			CreditGateway::class,
			PayLaterGateway::class,
			PayNowGateway::class,
			PnxGateway::class,
		);

		$field_list['gateway_section'] = array(
			'title'    => '<hr>' . L10nHelper::__( '→ Display Order configuration' ),
			'type'     => 'title',
			'desc_tip' => false,
		);
		/** @see self::generate_custom_html() */
		$field_list['gateway_header'] = array(
			'type'     => 'custom',
			'html'     => <<<HTML
			<tr valign="top">
				<table class="form-table">
					<tr valign="top"><td class="wc_emails_wrapper" colspan="2">
						<table class="wc_gateways widefat" cellspacing="0">
							<thead>
								<tr>
									<th class="wc-gateway-settings-table-name">Order</th>
									<th class="wc-gateway-settings-table-name">Payment method</th>
								</tr>
							</thead>
							<tbody class="ui-sortable">
			HTML,
			'desc_tip' => false,
		);

		foreach ( $gateway_list as $gateway ) {
			/** @see self::generate_table_order_html() */
			$field_list[ ( new $gateway() )->get_id() . '_order' ] = array(
				'type'      => 'table_order',
				'gateway'   => $gateway,
				'decorator' => '<tr>%s',
				'desc_tip'  => true,
			);
			/** @see self::generate_table_description_html() */
			$field_list[ ( new $gateway() )->get_id() . '_description' ] = array(
				'type'        => 'table_description',
				'description' => ( new $gateway() )->method_title,
				'desc_tip'    => true,
			);
		}

		/** @see self::generate_custom_html() */
		$field_list['gateway_footer'] = array(
			'type'     => 'custom',
			'html'     => <<<HTML
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</tr>
			HTML,
			'desc_tip' => false,
		);

		return $field_list;
	}

	/**
	 * Generate HTML for a custom field.
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 *
	 * @return string
	 */
	public function generate_custom_html( string $key, array $data ): string {

		return $data['html'] ?? '';
	}

	public function generate_table_order_html( string $key, array $data ): string {

		return '<td class="sort ui-sortable-handle" width="1%">'
				. '<div class="wc-item-reorder-nav">'
				. '<button type="button" class="wc-move-up" tabindex="0" aria-hidden="false" aria-label="Move the &quot;Payment method up">Move up</button>'
				. '<button type="button" class="wc-move-down" tabindex="0" aria-hidden="false" aria-label="Move the &quot;Payment method down">Move down</button>'
				. '<input type="hidden" name="gateway_order[]" value="alma_config_gateway">'
				. '</div>'
				. '</td>';
	}

	public function generate_table_description_html( string $key, array $data ): string {

		return '<td width="1%" class="wc-email-settings-table-name">'
				. $data['description']
				. '</td>';
	}

	public function generate_table_title_html( string $key, array $data ): string {

		return '<td width="1%" class="wc-email-settings-table-name">'
				. $data['title']
				. '</td>';
	}

	/**
	 * Generate HTML for a table toggle field.
	 *
	 * @param string $key
	 * @param array  $data
	 *
	 * @return string
	 */
	public function generate_table_toggle_html( string $key, array $data ): string {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'label'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['label'] ) ) {
			$data['label'] = $data['title'];
		}

		// Get the class for the toggle
		$toggle_class = 'woocommerce-input-toggle woocommerce-input-toggle--disabled';
		if ( $data['enabled'] ) {
			$toggle_class = 'woocommerce-input-toggle woocommerce-input-toggle--enabled';
		}

		// Begin building the HTML string
		$html  = '<td width="1%">';
		$html .= '<label for="' . esc_attr( $field_key ) . '">';
		$html .= '<a class="wc-alma-toggle-fee-plan-enabled" href="#' . esc_attr( $field_key ) . '" aria-label="Toggle the Payment Method">';
		$html .= '<span class="' . $toggle_class . '"></span>';
		$html .= '</a>';
		$html .= '</label><br />';
		$html .= $this->get_description_html( $data );
		$html .= '</td>';

		return $html;
	}

	public function generate_table_min_amount_html( string $key, array $data ): string {
		$field_key = $this->get_field_key( $key );

		return '<td width="1%">'
				. '<input type="number" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" value="' . esc_attr( $data['value'] ) . '" style="width: 80px;" step="0.01" min="' . $data['default'] . '">'
				. '&nbsp;<span>' . DisplayHelper::amount( $data['default'] ) . '</span>'
				. '</td>';
	}

	public function generate_table_max_amount_html( string $key, array $data ): string {
		$field_key = $this->get_field_key( $key );

		return '<td width="1%">'
				. '<input type="number" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" value="' . esc_attr( $data['value'] ) . '" style="width: 80px;" step="0.01" min="" max="' . $data['default'] . '">'
				. '&nbsp;<span>' . DisplayHelper::amount( $data['default'] ) . '</span>'
				. '</td>';
	}

	public function debug_fieldset(): array {

		/** @var AssetsHelper $assets_helper */
		$assets_helper = Plugin::get_container()->get( AssetsHelper::class );

		return array(
			'debug_section' => array(
				'title' => '<hr>' . L10nHelper::__( '→ Debug options', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'debug'         => array(
				'title'       => L10nHelper::__( 'Debug mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'checkbox',
				// translators: %s: Admin logs url.
				'label'       => L10nHelper::__(
					'Activate debug mode',
					'alma-gateway-for-woocommerce'
				) . sprintf(
					L10nHelper::__( '(<a href="%s">Go to logs</a>)' ),
					$assets_helper->get_admin_logs_url()
				),
				// translators: %s: The previous plugin version if exists.
				'description' => L10nHelper::__(
					'Enable logging info and errors to help debug any issue with the plugin (previous Alma version)',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'yes',
			),
		);
	}

	public function l10n_fieldset(): array {

		return array(
			'l10n_section' => array(
				'title'       => '<hr>' . L10nHelper::__( '→ Localization' ),
				'type'        => 'title',
				'description' => L10nHelper::__( 'Where\'s Alma is available?</a>' ),
			),
		);
	}

	public function generate_fee_to_pay_description( $translation, $fee_variable, $fee_fixed, $fee_description = '' ): string {
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

		return sprintf( '<br><b>%s</b> %s %s', $translation, $fees, $fee_description );
	}

	public function generate_fee_plan_title( FeePlan $fee_plan ): string {
		$section_title = '';

		if ( $fee_plan->isPayNow() ) {
			$section_title = L10nHelper::__( '→ Pay Now' );
			// translators: %d: number of installments.
			$toggle_label = sprintf(
				L10nHelper::__( 'Enable %d-installment payments with Alma' ),
				$fee_plan->getInstallmentsCount()
			);
		}

		if ( $fee_plan->isPnXOnly() || $fee_plan->isCredit() ) {
			// translators: %d: number of installments.
			$section_title = sprintf(
				L10nHelper::__( '→ %d-installment payment' ),
				$fee_plan->getInstallmentsCount()
			);
			// translators: %d: number of installments.
			$toggle_label = sprintf(
				L10nHelper::__( 'Enable %d-installment payments with Alma' ),
				$fee_plan->getInstallmentsCount()
			);
		}

		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_days   = $fee_plan->getDeferredDays();
			$deferred_months = $fee_plan->getDeferredMonths();
			if ( $deferred_days ) {
				// translators: %d: number of deferred days.
				$section_title = sprintf(
					L10nHelper::__( '→ D+%d-deferred payment' ),
					$deferred_days
				);
				// translators: %d: number of deferred days.
				$toggle_label = sprintf( L10nHelper::__( 'Enable D+%d-deferred payments with Alma' ), $deferred_days );
			}
			if ( $deferred_months ) {
				// translators: %d: number of deferred months.
				$section_title = sprintf(
					L10nHelper::__( '→ M+%d-deferred payment' ),
					$deferred_months
				);
				// translators: %d: number of deferred months.
				$toggle_label = sprintf(
					L10nHelper::__( 'Enable M+%d-deferred payments with Alma' ),
					$deferred_months
				);
			}
		}

		return $section_title;
	}

	public function generate_fee_plan_description( FeePlan $fee_plan ): string {

		/** @var OptionsService $options_service */
		$options_service = Plugin::get_container()->get( OptionsService::class );

		$you_can_offer = '';
		if ( $fee_plan->isPnXOnly() || $fee_plan->isCredit() ) {
			$you_can_offer = sprintf(
			// translators: %d: number of installments.
				L10nHelper::__( 'You can offer %1$d-installment payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
				$fee_plan->getInstallmentsCount(),
				$fee_plan->getMinPurchaseAmount() / 100,
				$fee_plan->getMaxPurchaseAmount() / 100
			);
		}

		if ( $fee_plan->isPayNow() ) {
			$you_can_offer = sprintf(
			// translators: %d: number of installments.
				L10nHelper::__( 'You can offer instant payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
				$fee_plan->getInstallmentsCount(),
				$fee_plan->getMinPurchaseAmount() / 100,
				$fee_plan->getMaxPurchaseAmount() / 100
			);
		}

		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_days   = $fee_plan->getDeferredDays();
			$deferred_months = $fee_plan->getDeferredMonths();
			if ( $deferred_days ) {
				$you_can_offer = sprintf(
				// translators: %d: number of deferred days.
					L10nHelper::__( 'You can offer D+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
					$deferred_days,
					$fee_plan->getMinPurchaseAmount() / 100,
					$fee_plan->getMaxPurchaseAmount() / 100
				);
			}
			if ( $deferred_months ) {
				$you_can_offer = sprintf(
				// translators: %d: number of deferred months.
					L10nHelper::__( 'You can offer M+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.' ),
					$deferred_months,
					$fee_plan->getMinPurchaseAmount() / 100,
					$fee_plan->getMaxPurchaseAmount() / 100
				);
			}
		}
		$fees_applied  = L10nHelper::__( 'Fees applied to each transaction for this plan:' );
		$you_pay       = $this->generate_fee_to_pay_description(
			L10nHelper::__( 'You pay:' ),
			$fee_plan->getMerchantFeeVariable() / 100,
			$fee_plan->getMerchantFeeFixed() / 100
		);
		$customer_pays = $this->generate_fee_to_pay_description(
			L10nHelper::__( 'Customer pays:' ),
			$fee_plan->getCustomerFeeVariable() / 100,
			$fee_plan->getCustomerFeeFixed() / 100,
			// translators: %s Link to alma dashboard.
			'<br>' . sprintf(
				L10nHelper::__( '<u>Note</u>: Customer fees are impacted by the usury rate, and will be adapted based on the limitations to comply with regulations. For more information, visit the Configuration page on your <a href="%s" target="_blank">Alma Dashboard</a>.' ),
				AssetsHelper::get_alma_dashboard_url( $options_service->get_environment(), 'conditions' )
			)
		);
		$customer_lending_pays = $this->generate_fee_to_pay_description(
			L10nHelper::__( 'Customer lending rate:' ),
			$fee_plan->getCustomerLendingRate() / 100,
			0
		);

		return sprintf(
			'<p>%s<br>%s %s %s %s</p>',
			$you_can_offer,
			$fees_applied,
			$you_pay,
			$customer_pays,
			$customer_lending_pays
		);
	}

	/**
	 * Inits a fee_plan's fields.
	 *
	 * @param FeePlan $fee_plan Fee plan definitions.
	 * @param array   $default_settings Default settings definitions.
	 *
	 * @return array  as field_form definition
	 */
	protected function init_fee_plan_fields( FeePlan $fee_plan, array $default_settings ): array {
		$key               = $fee_plan->getPlanKey();
		$min_amount_key    = 'min_amount_' . $key;
		$section_key       = $key . '_section';
		$max_amount_key    = 'max_amount_' . $key;
		$toggle_key        = 'enabled_' . $key;
		$class             = 'alma_fee_plan alma_fee_plan_' . $key;
		$default_enabled   = $default_settings['selected_fee_plan'] === $key ? 'yes' : 'no';
		$custom_attributes = array(
			'required' => 'required',
			'min'      => $fee_plan->getMinPurchaseAmount() / 100,
			'max'      => $fee_plan->getMaxPurchaseAmount() / 100,
			'step'     => 0.01,
		);

		$section_title = '';
		$toggle_label  = '';

		if ( $fee_plan->isPayNow() ) {
			$section_title = L10nHelper::__( '→ Pay Now' );
			// translators: %d: number of installments.
			$toggle_label = sprintf(
				L10nHelper::__( 'Enable %d-installment payments with Alma' ),
				$fee_plan->getInstallmentsCount()
			);
		}

		if ( $fee_plan->isPnXOnly() || $fee_plan->isCredit() ) {
			// translators: %d: number of installments.
			$section_title = sprintf(
				L10nHelper::__( '→ %d-installment payment' ),
				$fee_plan->getInstallmentsCount()
			);
			// translators: %d: number of installments.
			$toggle_label = sprintf(
				L10nHelper::__( 'Enable %d-installment payments with Alma' ),
				$fee_plan->getInstallmentsCount()
			);
		}

		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_days   = $fee_plan->getDeferredDays();
			$deferred_months = $fee_plan->getDeferredMonths();
			if ( $deferred_days ) {
				// translators: %d: number of deferred days.
				$section_title = sprintf(
					L10nHelper::__( '→ D+%d-deferred payment' ),
					$deferred_days
				);
				// translators: %d: number of deferred days.
				$toggle_label = sprintf( L10nHelper::__( 'Enable D+%d-deferred payments with Alma' ), $deferred_days );
			}
			if ( $deferred_months ) {
				// translators: %d: number of deferred months.
				$section_title = sprintf(
					L10nHelper::__( '→ M+%d-deferred payment' ),
					$deferred_months
				);
				// translators: %d: number of deferred months.
				$toggle_label = sprintf(
					L10nHelper::__( 'Enable M+%d-deferred payments with Alma' ),
					$deferred_months
				);
			}
		}

		return array(
			$section_key => array(
				'title'       => $section_title,
				'type'        => 'alma_fee_plan_section',
				'description' => $this->generate_fee_plan_description( $fee_plan ),
				'fee_plan,'   => $fee_plan,
			),
		);
	}
}
