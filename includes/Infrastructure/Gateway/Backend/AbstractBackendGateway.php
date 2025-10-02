<?php

namespace Alma\Gateway\Infrastructure\Gateway\Backend;

use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Domain\Exception\AlmaException;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\UrlHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Plugin;

class AbstractBackendGateway extends AbstractGateway {

	public const FIELD_LIVE_API_KEY = 'live_api_key';
	public const FIELD_TEST_API_KEY = 'test_api_key';
	public const MIN_AMOUNT_SUFFIX  = 'min_amount';
	public const MAX_AMOUNT_SUFFIX  = 'max_amount';
	public const ENABLED_SUFFIX     = 'enabled';
	public const ENABLED_PREFIX     = 'general';
	public const FIELD_MERCHANT_ID  = 'merchant_id';

	/**
	 * This gateway is not meant to process payments and throws an exception if called.
	 *
	 * @throws AlmaException
	 */
	public function process_payment( $order_id ): array {
		throw new AlmaException( 'This gateway is not meant to process payments.' );
	}

	/**
	 * Generate HTML components for the settings form fields.
	 *
	 * Override to allow to generate the HTML with decorators.
	 *
	 * @param array $form_fields
	 * @param true  $echo Not used, but kept for compatibility.
	 *
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
				'description' => L10nHelper::__( 'You can find your API keys on your Alma dashboard' ),
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
			self::FIELD_MERCHANT_ID  => array(
				'title'    => L10nHelper::__( 'Merchant Id' ),
				'type'     => 'text',
				'desc_tip' => true,
				'disabled' => true,
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
	 * Define the widget section.
	 *
	 * @return array[]
	 */
	public function widget_fieldset(): array {

		return array(
			'widgets_section'        => array(
				'title'       => '<hr>' . L10nHelper::__( '→ Display Alma widgets' ),
				'type'        => 'title',
				'description' => L10nHelper::__( 'Display Alma widget on cart and product page.' ),
				'desc_tip'    => false,
			),
			'widget_cart_enabled'    => array(
				'title'    => L10nHelper::__( 'Enable/Disable' ),
				'type'     => 'checkbox',
				'label'    => L10nHelper::__( 'Enable widget on cart page' ),
				'default'  => 'yes',
				'desc_tip' => false,
			),
			'widget_product_enabled' => array(
				'title'    => L10nHelper::__( 'Enable/Disable' ),
				'type'     => 'checkbox',
				'label'    => L10nHelper::__( 'Enable widget on product page' ),
				'default'  => 'yes',
				'desc_tip' => false,
			),
		);
	}

	/**
	 * Define the fee plan section.
	 * @throws FeePlanRepositoryException
	 */
	public function fee_plan_fieldset(): array {

		/** @var ConfigService $options_service */
		$options_service = Plugin::get_container()->get( ConfigService::class );
		$environment     = $options_service->getEnvironment();

		/** @var FeePlanRepository $fee_plan_repository */
		$fee_plan_repository   = Plugin::get_container( true )->get( FeePlanRepository::class );
		$fee_plan_list_adapter = $fee_plan_repository->getAll( true );

		$field_list['fee_plan_section'] = array(
			'title'    => '<hr>' . L10nHelper::__( '→ Fee plans configuration' ),
			'type'     => 'title',
			'desc_tip' => false,
		);
		/** @uses self::generate_custom_html() */
		$field_list['fee_plan_header'] = array(
			'type'     => 'custom',
			'html'     => <<<'HTML'
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

		/** @var FeePlanAdapter $fee_plan_adapter */
		foreach ( $fee_plan_list_adapter as $fee_plan_adapter ) {
			$fee_plan_display_data = L10nHelper::generate_fee_plan_display_data( $fee_plan_adapter, $environment );
			/** @uses self::generate_table_title_html() */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_title' ] = array(
				'type'      => 'table_title',
				'decorator' => '<tr data-fee_plan_key="' . $fee_plan_adapter->getPlanKey() . '">%s',
				'desc_tip'  => true,
				'title'     => $fee_plan_display_data['title'],
			);
			/**
			 * @uses self::generate_table_toggle_html()
			 */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_enabled' ] = array(
				'type'        => 'table_toggle',
				'fee_plan'    => $fee_plan_adapter,
				'description' => $fee_plan_display_data['toggle_label'],
				'desc_tip'    => true,
				'enabled'     => $fee_plan_adapter->isEnabled() ? '1' : '0',
			);
			/** @uses self::generate_table_description_html() */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_description' ] = array(
				'type'        => 'table_description',
				'description' => $fee_plan_display_data['description'],
				'desc_tip'    => true,
			);
			/** @uses self::generate_table_min_amount_html() */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_min_amount' ] = array(
				'type'     => 'table_min_amount',
				'desc_tip' => true,
				'default'  => DisplayHelper::price_to_euro( $fee_plan_adapter->getMinPurchaseAmount() ),
				'value'    => DisplayHelper::price_to_euro( $fee_plan_adapter->getOverrideMinPurchaseAmount() ),
			);
			/** @uses self::generate_table_max_amount_html() */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_max_amount' ] = array(
				'type'      => 'table_max_amount',
				'desc_tip'  => true,
				'default'   => DisplayHelper::price_to_euro( $fee_plan_adapter->getMaxPurchaseAmount() ),
				'value'     => DisplayHelper::price_to_euro( $fee_plan_adapter->getOverrideMaxPurchaseAmount() ),
				'decorator' => '%s</tr>',
			);
		}

		/** @uses self::generate_custom_html() */
		$field_list['fee_plan_footer'] = array(
			'type'     => 'custom',
			'html'     => <<<'HTML'
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
		/** @uses self::generate_custom_html() */
		$field_list['gateway_header'] = array(
			'type'     => 'custom',
			'html'     => <<<'HTML'
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
			/** @uses self::generate_table_order_html() */
			$field_list[ ( new $gateway() )->get_id() . '_order' ] = array(
				'type'      => 'table_order',
				'gateway'   => $gateway,
				'decorator' => '<tr>%s',
				'desc_tip'  => true,
			);
			/** @uses self::generate_table_description_html() */
			$field_list[ ( new $gateway() )->get_id() . '_description' ] = array(
				'type'        => 'table_description',
				'description' => ( new $gateway() )->method_title,
				'desc_tip'    => true,
			);
		}

		/** @uses self::generate_custom_html() */
		$field_list['gateway_footer'] = array(
			'type'     => 'custom',
			'html'     => <<<'HTML'
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
			'desc_tip'          => true,
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
		$html .= '<input type="hidden" name="' . esc_attr( $field_key ) . '" value="' . esc_attr( $data['enabled'] ) . '">';
		$html .= '<label for="' . esc_attr( $field_key ) . '">';
		$html .= '<a class="wc-alma-toggle-fee-plan-enabled" href="#' . esc_attr( $field_key ) . '" aria-label="' . $data['description'] . '" title="' . $data['description'] . '">';
		$html .= '<span class="' . $toggle_class . '"></span>';
		$html .= '</a>';
		$html .= '</label>';
		$html .= '</td>';

		return $html;
	}

	/**
	 * Validate Toggle in Table Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 *
	 * @return bool True if the toggle is enabled, false otherwise.
	 */
	public function validate_table_toggle_field( $key, $value ): bool {
		if ( ! empty( $value ) && '1' === $value ) {
			return true;
		}

		return false;
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
				. '<input type="number" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" value="' . esc_attr( $data['value'] ) . '" style="width: 80px;" step="0.01" max="' . $data['default'] . '">'
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
					UrlHelper::getAdminLogsUrl()
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

	public function excluded_categories_fieldset(): array {
		/** @var ProductCategoryRepository $product_category_repository */
		$product_category_repository = Plugin::get_container()->get( ProductCategoryRepository::class );

		return array(
			'excluded_categories_section' => array(
				'title'       => '<hr>' . L10nHelper::__( '→ Excluded Categories' ),
				'type'        => 'title',
				'description' => L10nHelper::__( 'Define the categories on which Alma doesn\'t apply' ),
				'desc_tip'    => false,
			),
			'excluded_products_list'      => array(
				'title'       => L10nHelper::__( 'Excluded product categories' ),
				'type'        => 'multiselect',
				'description' => L10nHelper::__( 'Exclude all virtual/downloadable product categories, as you cannot sell them with Alma' ),
				'desc_tip'    => true,
				'css'         => 'height: 150px;',
				'options'     => $product_category_repository->getAll(),
			),
			'excluded_products_message'   => array(
				'title'       => L10nHelper::__( 'Non-eligibility message for excluded products' ),
				'type'        => 'text',
				'description' => L10nHelper::__( 'Message displayed below the cart totals when it contains excluded products' ),
				'desc_tip'    => true,
			),
		);
	}
}
