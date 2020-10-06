<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

class Alma_WC_Payment_Gateway extends WC_Payment_Gateway {
	const GATEWAY_ID = 'alma';

	private $logger;

	public function __construct() {
		$this->id                 = self::GATEWAY_ID;
		$this->has_fields         = true;
		$this->method_title       = __( 'Alma monthly payments', 'alma-woocommerce-gateway' );
		$this->method_description = __( 'Easily provide monthly payments to your customers, risk-free!', 'alma-woocommerce-gateway' );

		$this->logger = new Alma_WC_Logger();

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		add_filter(
			'woocommerce_settings_api_sanitized_fields_' . $this->id,
			array( $this, 'on_settings_save' )
		);
	}

	/**
	 * Get option from DB.
	 *
	 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @param  string $key Option key.
	 * @param  mixed  $empty_value Value when empty.
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) {
		$option = parent::get_option( $key, $empty_value );

		if ( in_array( $key, Alma_WC_Settings::AMOUNT_KEYS ) ) {
			return strval( alma_wc_price_from_cents( $option ) );
		}

		return $option;
	}

	public function on_settings_save( $settings ) {
		// convert euros to cents.
		foreach ( Alma_WC_Settings::AMOUNT_KEYS as $key ) {
			if ( $settings[ $key ] ) {
				$settings[ $key ] = alma_wc_price_to_cents( $settings[ $key ] );
			}
		}

		if ( ! empty( alma_wc_plugin()->settings->get_active_api_key() ) ) {
			try {
				$merchant = alma_wc_plugin()->get_alma_client()->merchants->me();

				// store merchant id
				$settings['merchant_id'] = $merchant->id;

				foreach ( $merchant->fee_plans as $fee_plan ) {
					if ( ! $fee_plan['allowed'] ) {
						$installments       = $fee_plan['installments_count'];
						$default_min_amount = $fee_plan['min_purchase_amount'];
						$default_max_amount = $fee_plan['max_purchase_amount'];

						// force disable not available fee_plans to prevent showing them in checkout.
						$settings[ "enabled_${installments}x" ] = 'no';
						// reset min and max amount for disabled plans to prevent multiplication by 100 on each save.
						$settings[ "min_amount_${installments}x" ] = $default_min_amount;
						$settings[ "max_amount_${installments}x" ] = $default_max_amount;
					}
				}
			} catch ( \Alma\API\RequestError $e ) {
				alma_wc_plugin()->handle_settings_exception( $e );
			}
		}

		return $settings;
	}

	public function get_icon() {
		$icon_url = alma_wc_plugin()->get_asset_url( 'images/alma_logo.svg' );
		$icon     = '<img src="' . WC_HTTPS::force_https_url( $icon_url ) . '" alt="' . esc_attr( $this->get_title() ) . '" style="width: auto !important; height: 25px !important; border: none !important;">';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	public function init_form_fields() {
		$need_keys = empty( alma_wc_plugin()->settings->get_active_api_key() );

		$default_settings = Alma_WC_Settings::get_default_settings();

		if ( $need_keys ) {
			$keys_title = __( '→ Start by filling in your API keys', 'alma-woocommerce-gateway' );
		} else {
			$keys_title = __( '→ API configuration', 'alma-woocommerce-gateway' );
		}

		$enabled_option = array(
			'title'   => __( 'Enable/Disable', 'alma-woocommerce-gateway' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable monthly payments with Alma', 'alma-woocommerce-gateway' ),
			'default' => $default_settings['enabled'],
		);

		$api_key_fields = array(
			'keys_section' => array(
				'title'       => '<hr />' . $keys_title,
				'type'        => 'title',
				'description' => __( 'You can find your API keys on <a href="https://dashboard.getalma.eu/security" target="_blank">your Alma dashboard</a>', 'alma-woocommerce-gateway' ),
			),
			'live_api_key' => array(
				'title' => __( 'Live API key', 'alma-woocommerce-gateway' ),
				'type'  => 'text',
			),
			'test_api_key' => array(
				'title' => __( 'Test API key', 'alma-woocommerce-gateway' ),
				'type'  => 'text',
			),
			'environment'  => array(
				'title'       => __( 'API Mode', 'alma-woocommerce-gateway' ),
				'type'        => 'select',
				'description' => __( 'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.', 'alma-woocommerce-gateway' ),
				'default'     => $default_settings['environment'],
				'options'     => array(
					'test' => __( 'Test', 'alma-woocommerce-gateway' ),
					'live' => __( 'Live', 'alma-woocommerce-gateway' ),
				),
			),
		);

		$settings_fields = array(
			'enabled' => $enabled_option,
		);

		if ( ! $need_keys ) {
			try {
				$merchant = alma_wc_plugin()->get_alma_client()->merchants->me();

				foreach ( $merchant->fee_plans as $fee_plan ) {
					if ( $fee_plan['allowed'] ) {
						$installments          = $fee_plan['installments_count'];
						$default_min_amount    = alma_wc_price_from_cents( $fee_plan['min_purchase_amount'] );
						$default_max_amount    = alma_wc_price_from_cents( $fee_plan['max_purchase_amount'] );
						$merchant_fee_fixed    = alma_wc_price_from_cents( $fee_plan['merchant_fee_fixed'] );
						$merchant_fee_variable = $fee_plan['merchant_fee_variable'] / 100; // percent.
						$customer_fee_fixed    = alma_wc_price_from_cents( $fee_plan['customer_fee_fixed'] );
						$customer_fee_variable = $fee_plan['customer_fee_variable'] / 100; // percent.

						$settings_fields = array_merge(
							$settings_fields,
							array(
								"${installments}x_section" => array(
									'title'       => '<hr />' . sprintf( __( '→ %d-installment payment', 'alma-woocommerce-gateway' ), $installments ),
									'type'        => 'title',
									'description' => $this->get_fee_plan_description( $installments, $default_min_amount, $default_max_amount, $merchant_fee_fixed, $merchant_fee_variable, $customer_fee_fixed, $customer_fee_variable ),
								),
								"enabled_${installments}x" => array(
									'title'   => __( 'Enable/Disable', 'alma-woocommerce-gateway' ),
									'type'    => 'checkbox',
									'label'   => sprintf( __( 'Enable %d-installment payments with Alma', 'alma-woocommerce-gateway' ), $installments ),
									'default' => $default_settings[ "enabled_${installments}x" ],
								),
								"min_amount_${installments}x" => array(
									'title'             => __( 'Minimum amount', 'alma-woocommerce-gateway' ),
									'type'              => 'number',
									'css'               => 'width: 100px;',
									'custom_attributes' => array(
										'required' => 'required',
										'min'      => $default_min_amount,
										'max'      => $default_max_amount,
										'step'     => 0.01,
									),
									'default'           => alma_wc_price_to_cents( $default_min_amount ),
								),
								"max_amount_${installments}x" => array(
									'title'             => __( 'Maximum amount', 'alma-woocommerce-gateway' ),
									'type'              => 'number',
									'css'               => 'width: 100px;',
									'custom_attributes' => array(
										'required' => 'required',
										'min'      => $default_min_amount,
										'max'      => $default_max_amount,
										'step'     => 0.01,
									),
									'default'           => alma_wc_price_to_cents( $default_max_amount ),
								),
							)
						);
					}
				}
			} catch ( \Alma\API\RequestError $e ) {
				alma_wc_plugin()->handle_settings_exception( $e );
			}
		}

		$settings_fields = array_merge(
			$settings_fields,
			array(
				'general_section'                      => array(
					'title' => '<hr />' . __( '→ General configuration', 'alma-woocommerce-gateway' ),
					'type'  => 'title',
				),
				'title'                                => array(
					'title'       => __( 'Title', 'alma-woocommerce-gateway' ),
					'type'        => 'text',
					'description' => __( 'This controls the payment method name which the user sees during checkout.', 'alma-woocommerce-gateway' ),
					'default'     => $default_settings['title'],
					'desc_tip'    => true,
				),
				'description'                          => array(
					'title'       => __( 'Description', 'alma-woocommerce-gateway' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => __( 'This controls the payment method description which the user sees during checkout.', 'alma-woocommerce-gateway' ),
					'default'     => $default_settings['description'],
				),

				/*
				 We only support Euros at the moment, so there's no need for an option
				'active_currencies'         => array(
					'title'       => __( 'Allowed currencies', 'alma-woocommerce-gateway' ),
					'type'        => 'multiselect',
					'desc_tip'    => true,
					'description' => __( 'Choose which currencies you want to accept monthly payments with', 'alma-woocommerce-gateway' ),
					'default'     => 'EUR',
					'options'     => array(
						'EUR' => __( 'Euros (€)', 'alma-woocommerce-gateway' ),
					),
				),
				*/

				'display_product_eligibility'          => array(
					'title'   => __( 'Product eligibility notice', 'alma-woocommerce-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Display a message about product eligibility for monthly payments', 'alma-woocommerce-gateway' ),
					'default' => $default_settings['display_product_eligibility'],
				),
				'display_cart_eligibility'             => array(
					'title'   => __( 'Cart eligibility notice', 'alma-woocommerce-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Display a message about cart eligibility for monthly payments', 'alma-woocommerce-gateway' ),
					'default' => $default_settings['display_cart_eligibility'],
				),
				'cart_is_eligible_message'             => array(
					'title'       => __( 'Eligible cart message', 'alma-woocommerce-gateway' ),
					'type'        => 'text',
					'description' => __( 'Message displayed below the cart totals when it is eligible for monthly payments', 'alma-woocommerce-gateway' ),
					'desc_tip'    => true,
					'default'     => $default_settings['cart_is_eligible_message'],
				),
				'cart_not_eligible_message'            => array(
					'title'       => __( 'Non-eligible cart message', 'alma-woocommerce-gateway' ),
					'type'        => 'text',
					'description' => __( 'Message displayed below the cart totals when it is not eligible for monthly payments', 'alma-woocommerce-gateway' ),
					'desc_tip'    => true,
					'default'     => $default_settings['cart_not_eligible_message'],
				),
				'product_not_eligible_message'         => array(
					'title'       => __( 'Non-eligible product message', 'alma-woocommerce-gateway' ),
					'type'        => 'text',
					'description' => __( 'Message displayed above the "add to cart" button when product is not eligible for monthly payments', 'alma-woocommerce-gateway' ),
					'desc_tip'    => true,
					'default'     => $default_settings['product_not_eligible_message'],
				),

				'excluded_products_list'               => array(
					'title'       => __( 'Excluded product categories', 'alma-woocommerce-gateway' ),
					'type'        => 'multiselect',
					'description' => __( 'Exclude all virtual/downloadable product categories, as you cannot sell them with Alma', 'alma-woocommerce-gateway' ),
					'desc_tip'    => true,
					'css'         => 'height: 150px;',
					'options'     => $this->product_categories_options(),
				),

				'cart_not_eligible_message_gift_cards' => array(
					'title'       => __( 'Non-eligibility message for excluded products', 'alma-woocommerce-gateway' ),
					'type'        => 'text',
					'description' => __( 'Message displayed below the cart totals when it contains excluded products', 'alma-woocommerce-gateway' ),
					'desc_tip'    => true,
					'default'     => $default_settings['cart_not_eligible_message_gift_cards'],
				),
			)
		);

		$debug_fields = array(
			'debug_section' => array(
				'title' => '<hr />' . __( '→ Debug options', 'alma-woocommerce-gateway' ),
				'type'  => 'title',
			),
			'debug'         => array(
				'title'       => __( 'Debug mode', 'alma-woocommerce-gateway' ),
				'type'        => 'checkbox',
				'label'       => __( 'Activate debug mode', 'alma-woocommerce-gateway' ) . sprintf( __( ' (<a href="%s">Go to logs</a>)', 'alma-woocommerce-gateway' ), alma_wc_plugin()->get_admin_logs_url() ),
				'description' => __( 'Enable logging info and errors to help debug any issue with the plugin', 'alma-woocommerce-gateway' ),
				'desc_tip'    => true,
				'default'     => $default_settings['debug'],
			),
		);

		if ( $need_keys ) {
			$this->form_fields = array_merge( array( 'enabled' => $enabled_option ), $api_key_fields, $debug_fields );
		} else {
			$this->form_fields = array_merge( $settings_fields, $api_key_fields, $debug_fields );
		}
	}

	public function needs_setup() {
		$this->update_option( 'enabled', 'yes' );

		return true;
	}

	public function init_settings() {
		parent::init_settings();
		alma_wc_plugin()->settings->update_from( $this->settings );
	}

	public function process_admin_options() {
		$saved = parent::process_admin_options();

		alma_wc_plugin()->settings->update_from( $this->settings );
		alma_wc_plugin()->check_settings();

		return $saved;
	}

	public function is_available() {
		// If we're in the context of the admin or an API call, don't
		if ( is_admin() || alma_wc_is_rest_call() ) {
			return parent::is_available();
		}

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return false;
		}

		$locale = get_locale();
		if ( $locale !== 'fr_FR' ) {
			$this->logger->info( "Locale {$locale} not supported - Not displaying Alma" );

			return false;
		}

		$currency = get_woocommerce_currency();
		if ( $currency !== 'EUR' ) {
			$this->logger->info( "Currency {$currency} not supported - Not displaying Alma" );

			return false;
		}

		if ( ! count( alma_wc_get_eligible_installments_for_cart_according_to_settings() ) ) {
			return false;
		}

		if (
			array_key_exists( 'excluded_products_list', $this->settings ) &&
			is_array( $this->settings['excluded_products_list'] ) &&
			count( $this->settings['excluded_products_list'] ) > 0
		) {
			foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
				$product = $cart_item['data'];

				foreach ( $this->settings['excluded_products_list'] as $category_slug ) {
					if ( has_term( $category_slug, 'product_cat', $product->get_id() ) ) {
						return false;
					}
				}
			}
		}

		try {
			$eligibility = $alma->payments->eligibility( Alma_WC_Payment::from_cart() );
		} catch ( \Alma\API\RequestError $e ) {
			$this->logger->error( 'Error while checking payment eligibility: ' . print_r( $e, true ) );

			return false;
		}

		return $eligibility->isEligible && parent::is_available();
	}

	public function payment_fields() {
		echo wpautop( wp_kses_post( $this->description ) );

		$eligible_installments_list = alma_wc_get_eligible_installments_for_cart_according_to_settings();

		$default_installments = self::get_default_pnx( $eligible_installments_list );

		?>
		<div class="form-row">
			<p><?php echo esc_html__( 'How many installments do you want to pay?', 'alma-woocommerce-gateway' ); ?> <span class="required">*</span></p>
			<p>
				<?php foreach ( $eligible_installments_list as $n ) { ?>
				<input
					type="radio"
					id="alma_installments_count_<?php echo esc_attr( $n ); ?>"
					name="alma_installments_count"
					value="<?php echo esc_attr( $n ); ?>"
					<?php if ( $n === $default_installments ) { ?>
					checked
					<?php	} ?>
				>
				<label
					class="checkbox"
					for="alma_installments_count_<?php echo esc_attr( $n ); ?>"
				>
					<?php echo sprintf( esc_html__( '%d installments', 'alma-woocommerce-gateway' ), esc_html( $n ) ); ?>
				</label>
				<?php } ?>
			</p>
		</div>
		<?php
	}

	public function validate_fields() {
		if ( empty( $_POST['alma_installments_count'] ) ) {
			wc_add_notice( '<strong>Installments count</strong> is required.', 'error' );
			return false;
		}
		$allowed_values = array_map( 'strval', alma_wc_get_eligible_installments_for_cart_according_to_settings() );
		if ( ! in_array( $_POST['alma_installments_count'], $allowed_values, true ) ) {
			wc_add_notice( '<strong>Installments count</strong> is invalid.', 'error' );
			return false;
		}
		return true;
	}

	public function process_payment( $order_id ) {
		$error_msg = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', 'alma-woocommerce-gateway' );

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			wc_add_notice( $error_msg, 'error' );

			return array(
				'result' => 'error',
			);
		}

		try {
			$payment = $alma->payments->createPayment( Alma_WC_Payment::from_order( $order_id, intval( $_POST['alma_installments_count'] ) ) );
		} catch ( \Alma\API\RequestError $e ) {
			$this->logger->error( 'Error while creating payment: ' . $e->getMessage() );
			wc_add_notice( $error_msg, 'error' );

			return array( 'result' => 'error' );
		}

		// Redirect user to our payment page
		return array(
			'result'   => 'success',
			'redirect' => $payment->url,
		);
	}

	private function _redirect_to_cart_with_error( $error_msg ) {
		wc_add_notice( $error_msg, 'error' );
		wp_redirect( wc_get_cart_url() );

		return array(
			'result'   => 'error',
			'redirect' => wc_get_cart_url(),
		);
	}

	public function validate_payment_on_customer_return( $payment_id ) {
		try {
			$order = Alma_WC_Payment_Validator::validate_payment( $payment_id );
		} catch ( AlmaPaymentValidationError $e ) {
			$error_msg = __( 'There was an error when validating your payment.<br>Please try again or contact us if the problem persists.', 'alma-woocommerce-gateway' );
			return $this->_redirect_to_cart_with_error( $error_msg );
		} catch ( \Exception $e ) {
			return $this->_redirect_to_cart_with_error( $e->getMessage() );
		}

		// Redirect user to the order confirmation page
		$return_url = $this->get_return_url( $order->get_wc_order() );
		wp_redirect( $return_url );

		return array(
			'result'   => 'success',
			'redirect' => $return_url,
		);
	}

	public function validate_payment_from_ipn( $payment_id ) {
		try {
			Alma_WC_Payment_Validator::validate_payment( $payment_id );
		} catch ( \Exception $e ) {
			status_header( 500 );
			wp_send_json( array( 'error' => $e->getMessage() ) );
		}

		wp_send_json( array( 'success' => true ) );
	}

	private function product_categories_options() {
		$orderby    = 'name';
		$order      = 'asc';
		$hide_empty = false;
		$cat_args   = array(
			'orderby'    => $orderby,
			'order'      => $order,
			'hide_empty' => $hide_empty,
		);

		$product_categories = get_terms( 'product_cat', $cat_args );

		$options = array();
		if ( ! empty( $product_categories ) ) {
			foreach ( $product_categories as $key => $category ) {
				$options[ $category->slug ] = $category->name;
			}
		}

		return $options;
	}

	private function get_fee_plan_description( $installments, $min_amount, $max_amount, $merchant_fee_fixed, $merchant_fee_variable, $customer_fee_fixed, $customer_fee_variable ) {
		$description = '<p>';

		$description .= sprintf( __( 'You can offer %1$d-installment payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-woocommerce-gateway' ), $installments, $min_amount, $max_amount )
			. '<br />'
			. __( 'Fees applied to each transaction for this plan:', 'alma-woocommerce-gateway' );

		if ( $merchant_fee_variable || $merchant_fee_fixed ) {
			$description .= '<br />';
			$description .= '<b>' . __( 'You pay:', 'alma-woocommerce-gateway' ) . '</b> ';
		}

		if ( $merchant_fee_variable ) {
			$description .= $merchant_fee_variable . '%';
		}

		if ( $merchant_fee_fixed ) {
			if ( $merchant_fee_variable ) {
				$description .= ' + ';
			}
			$description .= $merchant_fee_fixed . '€';
		}

		if ( $customer_fee_variable || $customer_fee_fixed ) {
			$description .= '<br />';
			$description .= '<b>' . __( 'Customer pays:', 'alma-woocommerce-gateway' ) . '</b> ';
		}

		if ( $customer_fee_variable ) {
			$description .= $customer_fee_variable . '%';
		}

		if ( $customer_fee_fixed ) {
			if ( $customer_fee_variable ) {
				$description .= ' + ';
			}
			$description .= $customer_fee_fixed . '€';
		}

		$description .= '</p>';

		return $description;
	}

	/**
	 * Get default pnx according to eligible pnx list.
	 *
	 * @param int[] $pnx_list the list of aligible pnx.
	 *
	 * @return int|null
	 */
	private static function get_default_pnx( $pnx_list ) {
		if ( ! count( $pnx_list ) ) {
			return null;
		}

		if ( in_array( 3, $pnx_list, true ) ) {
			return 3;
		}

		return end( $pnx_list );
	}
}
