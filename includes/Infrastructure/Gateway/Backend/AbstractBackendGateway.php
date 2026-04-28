<?php

namespace Alma\Gateway\Infrastructure\Gateway\Backend;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Domain\Entity\FeePlanList;
use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Helper\AlmaHelper;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Gateway\GatewayException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Infrastructure\Helper\UrlHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Plugin;

class AbstractBackendGateway extends AbstractGateway {


	/**
	 * This gateway is not meant to process payments and throws an exception if called.
	 *
	 * @throws GatewayException
	 */
	public function process_payment( $order_id ): array {
		throw new GatewayException( 'This gateway is not meant to process payments.' );
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
				'title'    => __( 'Enable/Disable', 'alma-gateway-for-woocommerce' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable monthly payments with Alma', 'alma-gateway-for-woocommerce' ),
				'default'  => 'no',
				'desc_tip' => false,
				'class'    => 'wc-alma-toggle-enabled',
			),
		);
	}

	public function api_key_fieldset(): array {

		// Available options for the environment select field
		/** @var ConfigService $options_service */
		$options_service             = Plugin::get_container()->get( ConfigService::class );
		$environment_options         = array();
		$environment_options['test'] = __( 'Test', 'alma-gateway-for-woocommerce' );
		$environment_options['live'] = __( 'Live', 'alma-gateway-for-woocommerce' );

		return array(
			'keys_section'                               => array(
				'title'       => '<hr>' . __( '→ Start by filling in your API keys', 'alma-gateway-for-woocommerce' ),
				'type'        => 'title',
				'description' => __(
					'You can find your API keys on your Alma dashboard',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => false,
			),
			GatewayConfigurationForm::FIELD_LIVE_API_KEY => array(
				'title'    => __( 'Live API key', 'alma-gateway-for-woocommerce' ),
				'type'     => 'password',
				'desc_tip' => true,
			),
			GatewayConfigurationForm::FIELD_TEST_API_KEY => array(
				'title'    => __( 'Test API key', 'alma-gateway-for-woocommerce' ),
				'type'     => 'password',
				'desc_tip' => true,
			),
			GatewayConfigurationForm::FIELD_MERCHANT_ID  => array(
				'title'    => __( 'Merchant Id', 'alma-gateway-for-woocommerce' ),
				'type'     => 'text',
				'desc_tip' => true,
				'disabled' => true,
			),
			'environment'                                => array(
				'title'       => __( 'API Mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'select',
				'description' => __(
					'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'test',
				'options'     => $environment_options,
				'class'       => 'wc-enhanced-select',
			),
		);
	}

	public function display_fieldset(): array {

		if ( Plugin::get_instance()->is_configured( true ) ) {
			return $this->display_fieldset_definitions();
		}

		return array();
	}

	/**
	 * Raw display fieldset definitions, ungated.
	 * Use this when you need the field schema regardless of the current
	 * `is_configured` state (e.g. to backfill defaults during the first save).
	 */
	public function display_fieldset_definitions(): array {

		return array(
			'display_section' => array(
				'title' => '<hr>' . __( '→ Display options', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'in_page_enabled' =>
				array(
					'title'    => __( 'Activate In-Page Checkout', 'alma-gateway-for-woocommerce' ),
					'type'     => 'checkbox',
					'label'    => __(
						'Let your customers pay with Alma in a secure pop-up, without leaving your site.',
						'alma-gateway-for-woocommerce'
					),
					'default'  => 'yes',
					'desc_tip' => false,
					'class'    => 'wc-alma-toggle-enabled',
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
				'title'       => '<hr>' . __( '→ Display Alma widgets', 'alma-gateway-for-woocommerce' ),
				'type'        => 'title',
				'description' => __(
					'Display Alma widget on cart and product page.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => false,
			),
			'widget_product_enabled' => array(
				'title'    => __( 'Product eligibility', 'alma-gateway-for-woocommerce' ),
				'type'     => 'checkbox',
				'label'    => __( 'Display widget on product page', 'alma-gateway-for-woocommerce' ),
				'default'  => 'yes',
				'desc_tip' => false,
				'class'    => 'wc-alma-toggle-enabled',
			),
			'widget_cart_enabled'    => array(
				'title'    => __( 'Cart eligibility', 'alma-gateway-for-woocommerce' ),
				'type'     => 'checkbox',
				'label'    => __( 'Display widget on cart page', 'alma-gateway-for-woocommerce' ),
				'default'  => 'yes',
				'desc_tip' => false,
				'class'    => 'wc-alma-toggle-enabled',
			),
		);
	}

	/**
	 * Define the fee plan section.
	 */
	public function fee_plan_fieldset(): array {

		/** @var ConfigService $options_service */
		$options_service = Plugin::get_container()->get( ConfigService::class );
		$environment     = $options_service->getEnvironment();

		/** @var GatewayRepository $gateway_repository */
		$gateway_repository = Plugin::get_container()->get( GatewayRepository::class );

		/** @var FeePlanRepository $fee_plan_repository */
		$fee_plan_repository = Plugin::get_container()->get( FeePlanRepository::class );
		try {
			$fee_plan_list_adapter = $fee_plan_repository->getAll()->orderBy( $gateway_repository->findOrderedAlmaGateways() )->filterAvailable();
		} catch ( FeePlanRepositoryException $e ) {
			// No exception, just an empty list
			$this->logger_service->debug(
				'Can not get Fee Plans for gateway ' . $this->get_id(),
				array( 'exception' => $e )
			);
			$fee_plan_list_adapter = new FeePlanListAdapter( new FeePlanList() );
		}

		$field_list['fee_plan_section'] = array(
			'title'       => '<hr>' . __( '→ Fee plans configuration', 'alma-gateway-for-woocommerce' ),
			'description' => sprintf(
			// translators: %s: Alma dashboard URL.
				__(
					'only your <a href="%s" target="_blank">Alma dashboard</a> available fee plans are shown here.',
					'alma-gateway-for-woocommerce'
				),
				AlmaHelper::getAlmaDashboardUrl( $environment, 'conditions' )
			),
			'type'        => 'title',
			'desc_tip'    => false,
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
			$fee_plan_display_data = L10nHelper::generate_fee_plan_display_data( $fee_plan_adapter );
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
				'enabled'     => $fee_plan_adapter->isEnabled() || $fee_plan_adapter->getPlanKey() === 'general_3_0_0' ? '1' : '0',
			);
			/** @uses self::generate_table_description_html() */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_description' ] = array(
				'type'        => 'table_description',
				'description' => $fee_plan_display_data['description'],
				'desc_tip'    => true,
			);
			/** @uses self::generate_table_min_amount_html() */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_min_amount' ] = array(
				'type'        => 'table_min_amount',
				'desc_tip'    => true,
				'description' => sprintf(
				/* translators: %s: The maximum purchase amount */
					__( 'The minimum purchase amount allowed is %s€', 'alma-gateway-for-woocommerce' ),
					DisplayHelper::price_to_euro( $fee_plan_adapter->getMinPurchaseAmount() )
				),
				'default'     => DisplayHelper::price_to_euro( $fee_plan_adapter->getMinPurchaseAmount() ),
				'value'       => DisplayHelper::price_to_euro( $fee_plan_adapter->getOverrideMinPurchaseAmount() ),
			);
			/** @uses self::generate_table_max_amount_html() */
			$field_list[ $fee_plan_adapter->getPlanKey() . '_max_amount' ] = array(
				'type'        => 'table_max_amount',
				'desc_tip'    => true,
				'description' => sprintf(
				/* translators: %s: The maximum purchase amount */
					__( 'The maximum purchase amount allowed is %s€', 'alma-gateway-for-woocommerce' ),
					DisplayHelper::price_to_euro( $fee_plan_adapter->getMaxPurchaseAmount() )
				),
				'default'     => DisplayHelper::price_to_euro( $fee_plan_adapter->getMaxPurchaseAmount() ),
				'value'       => DisplayHelper::price_to_euro( $fee_plan_adapter->getOverrideMaxPurchaseAmount() ),
				'decorator'   => '%s</tr>',
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
	public function validate_table_toggle_field( string $key, string $value ): bool {
		if ( ! empty( $value ) && '1' === $value ) {
			return true;
		}

		return false;
	}

	public function generate_table_min_amount_html( string $key, array $data ): string {
		$field_key = $this->get_field_key( $key );

		return '<td width="1%">'
				. '<input type="number" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" value="' . esc_attr( $data['value'] ) . '" style="width: 80px;" step="0.01" min="' . $data['default'] . '">'
				. $this->get_tooltip_html( $data )
				. '</td>';
	}

	public function generate_table_max_amount_html( string $key, array $data ): string {
		$field_key = $this->get_field_key( $key );

		return '<td width="1%">'
				. '<input type="number" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" value="' . esc_attr( $data['value'] ) . '" style="width: 80px;" step="0.01" max="' . $data['default'] . '">'
				. $this->get_tooltip_html( $data )
				. '</td>';
	}

	public function debug_fieldset(): array {
		return array(
			'debug_section' => array(
				'title' => '<hr>' . __( '→ Debug options', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'debug'         => array(
				'title'       => __( 'Debug mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __(
					'Activate debug mode',
					'alma-gateway-for-woocommerce'
				) . sprintf(
								// translators: %s: Admin logs url.
					__( '(<a href="%s">Go to logs</a>)', 'alma-gateway-for-woocommerce' ),
					UrlHelper::getAdminLogsUrl()
				),
				// translators: %s: The previous plugin version if exists.
				'description' => __(
					'Enable logging info and errors to help debug any issue with the plugin (previous Alma version)',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => 'no',
				'class'       => 'wc-alma-toggle-enabled',
			),
		);
	}

	public function excluded_categories_fieldset(): array {
		/** @var ProductCategoryRepository $product_category_repository */
		$product_category_repository = Plugin::get_container()->get( ProductCategoryRepository::class );

		return array(
			'excluded_categories_section' => array(
				'title'       => '<hr>' . __( '→ Excluded Categories', 'alma-gateway-for-woocommerce' ),
				'type'        => 'title',
				'description' => __(
					'Define the categories on which Alma doesn\'t apply',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => false,
			),
			'excluded_products_list'      => array(
				'title'       => __( 'Excluded product categories', 'alma-gateway-for-woocommerce' ),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'description' => __(
					'Exclude all virtual/downloadable product categories, as you cannot sell them with Alma',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'css'         => 'height: 150px;',
				'options'     => $product_category_repository->getAll(),
			),
			'excluded_products_message'   => array(
				'title'       => __(
					'Non-eligibility message for excluded products',
					'alma-gateway-for-woocommerce'
				),
				'type'        => 'text',
				'description' => __(
					'Message displayed below the cart totals when it contains excluded products',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __(
					'Some products cannot be paid with monthly or deferred installments',
					'alma-gateway-for-woocommerce'
				),
			),
		);
	}

	/**
	 * Define the customize payment buttons text section.
	 *  All parameters are injected here are used for unit test
	 *  Let the fallback to the container for production use
	 *
	 * @return array[]
	 */
	public function customize_payment_buttons_text_fieldset(
		?PayNowGateway $payNowGateway = null,
		?PnxGateway $pnxGateway = null,
		?PayLaterGateway $payLaterGateway = null,
		?CreditGateway $creditGateway = null
	): array {
		/** @var PayNowGateway $payNowGateway */
		$payNowGateway = $payNowGateway ?? Plugin::get_container()->get( PayNowGateway::class );
		/** @var PnxGateway $pnxGateway */
		$pnxGateway = $pnxGateway ?? Plugin::get_container()->get( PnxGateway::class );
		/** @var PayLaterGateway $payLaterGateway */
		$payLaterGateway = $payLaterGateway ?? Plugin::get_container()->get( PayLaterGateway::class );
		/** @var CreditGateway $creditGateway */
		$creditGateway = $creditGateway ?? Plugin::get_container()->get( CreditGateway::class );

		if (
			! $payNowGateway->is_enabled() &&
			! $pnxGateway->is_enabled() &&
			! $payLaterGateway->is_enabled() &&
			! $creditGateway->is_enabled()
		) {
			return array();
		}

		$fields = array(
			'customize_payment_buttons_text_section' => array(
				'title'       => '<hr>' . __( '→ Customize payment button text', 'alma-gateway-for-woocommerce' ),
				'type'        => 'title',
				'description' => __(
					'Customize the text displayed on the Alma payment button on the checkout page',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => false,
			),
		);

		if ( $payNowGateway->is_enabled() ) {
			$fields = array_merge( $fields, $this->get_paynow_fields() );
		}

		if ( $pnxGateway->is_enabled() ) {
			$fields = array_merge( $fields, $this->get_pnx_fields() );
		}

		if ( $payLaterGateway->is_enabled() ) {
			$fields = array_merge( $fields, $this->get_paylater_fields() );
		}

		if ( $creditGateway->is_enabled() ) {
			$fields = array_merge( $fields, $this->get_credit_fields() );
		}

		return $fields;
	}

	/**
	 * Get paynow fields.
	 *
	 * @return array
	 */
	public function get_paynow_fields(): array {
		return array(
			'paynow_title'                   => array(
				'title' => sprintf( '<h2>%s:</h2>', __( 'Pay now', 'alma-gateway-for-woocommerce' ) ),
				'type'  => 'title',
			),
			PayNowGateway::TITLE_FIELD       => array(
				'title'       => __( 'Title', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method name which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Pay by credit card', 'alma-gateway-for-woocommerce' ),
			),
			PayNowGateway::DESCRIPTION_FIELD => array(
				'title'       => __( 'Description', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method description which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Fast and secured payments', 'alma-gateway-for-woocommerce' ),
			),
		);
	}

	/**
	 * Get pnx fields.
	 *
	 * @return array
	 */
	public function get_pnx_fields(): array {
		return array(
			'pnx_title'                   => array(
				'title' => sprintf(
					'<h2>%s:</h2>',
					__( 'Payments in 2, 3 and 4 installments', 'alma-gateway-for-woocommerce' )
				),
				'type'  => 'title',
			),
			PnxGateway::TITLE_FIELD       => array(
				'title'       => __( 'Title', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method name which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Pay in installments', 'alma-gateway-for-woocommerce' ),
			),
			PnxGateway::DESCRIPTION_FIELD => array(
				'title'       => __( 'Description', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method description which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Fast and secure payment by credit card', 'alma-gateway-for-woocommerce' ),
			),
		);
	}

	/**
	 * Get paylater fields.
	 *
	 * @return array
	 */
	public function get_paylater_fields(): array {
		return array(
			'paylater_title'                   => array(
				'title' => sprintf( '<h2>%s:</h2>', __( 'Deferred Payments', 'alma-gateway-for-woocommerce' ) ),
				'type'  => 'title',
			),
			PayLaterGateway::TITLE_FIELD       => array(
				'title'       => __( 'Title', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method name which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Pay later', 'alma-gateway-for-woocommerce' ),
			),
			PayLaterGateway::DESCRIPTION_FIELD => array(
				'title'       => __( 'Description', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method description which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Fast and secure payment by credit card', 'alma-gateway-for-woocommerce' ),
			),
		);
	}

	/**
	 * Get credit fields.
	 *
	 * @return array
	 */
	public function get_credit_fields(): array {
		return array(
			'credit_title'                   => array(
				'title' => sprintf(
					'<h2>%s:</h2>',
					__( 'Payments in more than 4 installments', 'alma-gateway-for-woocommerce' )
				),
				'type'  => 'title',
			),
			CreditGateway::TITLE_FIELD       => array(
				'title'       => __( 'Title', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method name which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Pay with financing', 'alma-gateway-for-woocommerce' ),
			),
			CreditGateway::DESCRIPTION_FIELD => array(
				'title'       => __( 'Description', 'alma-gateway-for-woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'This controls the payment method description which the user sees during checkout.',
					'alma-gateway-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => __( 'Fast and secure payment by credit card', 'alma-gateway-for-woocommerce' ),
			),
		);
	}
}
