<?php

use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;

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
		$this->has_fields         = false;
		$this->method_title       = __( 'Alma monthly payments', ALMA_WC_TEXT_DOMAIN );
		$this->method_description = __( 'Easily provide monthly payments to your customers, risk-free!', ALMA_WC_TEXT_DOMAIN );

		$this->logger = new Alma_WC_Logger();

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
	}

	public function get_icon() {
		$icon_url = alma_wc_plugin()->get_asset_url( 'images/alma_logo.svg' );
		$icon     = '<img src="' . WC_HTTPS::force_https_url( $icon_url ) . '" alt="' . esc_attr( $this->get_title() ) . '" style="width: auto !important; height: 25px !important; border: none !important;">';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	public function init_form_fields() {
		$need_keys = empty( alma_wc_plugin()->settings->get_active_api_key() );

		if ( $need_keys ) {
			$keys_title = __( '→ Start by filling in your API keys', ALMA_WC_TEXT_DOMAIN );
		} else {
			$keys_title = __( '→ API configuration', ALMA_WC_TEXT_DOMAIN );
		}

		$enabled_option = array(
			'title'   => __( 'Enable/Disable', ALMA_WC_TEXT_DOMAIN ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable monthly payments with Alma', ALMA_WC_TEXT_DOMAIN ),
			'default' => 'yes'
		);

		$api_key_fields = array(
			'keys_section' => array(
				'title'       => $keys_title,
				'type'        => 'title',
				'description' => __( 'You can find your API keys on <a href="https://dashboard.getalma.eu/security" target="_blank">your Alma dashboard</a>', ALMA_WC_TEXT_DOMAIN ),
			),
			'live_api_key' => array(
				'title' => __( 'Live API key', ALMA_WC_TEXT_DOMAIN ),
				'type'  => 'text',
			),
			'test_api_key' => array(
				'title' => __( 'Test API key', ALMA_WC_TEXT_DOMAIN ),
				'type'  => 'text',
			),
			'environment'  => array(
				'title'       => __( 'API Mode', ALMA_WC_TEXT_DOMAIN ),
				'type'        => 'select',
				'description' => __( 'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.', ALMA_WC_TEXT_DOMAIN ),
				'default'     => 'test',
				'options'     => array(
					'test' => __( 'Test', ALMA_WC_TEXT_DOMAIN ),
					'live' => __( 'Live', ALMA_WC_TEXT_DOMAIN ),
				),
			),
		);

		$settings_fields = array(
			'general_section' => array(
				'title' => __( '→ General configuration', ALMA_WC_TEXT_DOMAIN ),
				'type'  => 'title',
			),
			'enabled'         => $enabled_option,
			'title'           => array(
				'title'       => __( 'Title', ALMA_WC_TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => __( 'This controls the payment method name which the user sees during checkout.', ALMA_WC_TEXT_DOMAIN ),
				'default'     => __( 'Monthly Payments with Alma', ALMA_WC_TEXT_DOMAIN ),
				'desc_tip'    => true,
			),
			'description'     => array(
				'title'       => __( 'Description', ALMA_WC_TEXT_DOMAIN ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the payment method description which the user sees during checkout.', ALMA_WC_TEXT_DOMAIN ),
				'default'     => __( 'Pay in 3 monthly payments with your credit card.', ALMA_WC_TEXT_DOMAIN ),
			),

			/* We only support Euros at the moment, so there's no need for an option
			'active_currencies'         => array(
				'title'       => __( 'Allowed currencies', ALMA_WC_TEXT_DOMAIN ),
				'type'        => 'multiselect',
				'desc_tip'    => true,
				'description' => __( 'Choose which currencies you want to accept monthly payments with', ALMA_WC_TEXT_DOMAIN ),
				'default'     => 'EUR',
				'options'     => array(
					'EUR' => __( 'Euros (€)', ALMA_WC_TEXT_DOMAIN ),
				),
			),
			*/

			'display_cart_eligibility'  => array(
				'title'   => __( 'Cart eligibility notice', ALMA_WC_TEXT_DOMAIN ),
				'type'    => 'checkbox',
				'label'   => __( 'Display a message about cart eligibility for monthly payments', ALMA_WC_TEXT_DOMAIN ),
				'default' => 'yes'
			),
			'cart_is_eligible_message'  => array(
				'title'       => __( 'Eligibility message', ALMA_WC_TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => __( 'Message displayed below the cart totals when it is eligible for monthly payments', ALMA_WC_TEXT_DOMAIN ),
				'desc_tip'    => true,
				'default'     => __( 'Your cart is eligible for monthly payments', ALMA_WC_TEXT_DOMAIN ),
			),
			'cart_not_eligible_message' => array(
				'title'       => __( 'Non-eligibility message', ALMA_WC_TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => __( 'Message displayed below the cart totals when it is not eligible for monthly payments', ALMA_WC_TEXT_DOMAIN ),
				'desc_tip'    => true,
				'default'     => __( 'Your cart is not eligible for monthly payments', ALMA_WC_TEXT_DOMAIN ),
			),

            'excluded_products_list' => array(
                'title' => __( 'Excluded product categories', ALMA_WC_TEXT_DOMAIN ),
                'type' => 'multiselect',
                'description' => __( 'Exclude all virtual/downloadable product categories, as you cannot sell them with Alma', ALMA_WC_TEXT_DOMAIN ),
                'desc_tip'    => true,
                'css' => 'height: 150px;',
                'options' => $this->product_categories_options(),
            ),

            'cart_not_eligible_message_gift_cards' => array(
                'title'       => __( 'Non-eligibility message for excluded products', ALMA_WC_TEXT_DOMAIN ),
                'type'        => 'text',
                'description' => __( 'Message displayed below the cart totals when it contains excluded products', ALMA_WC_TEXT_DOMAIN ),
                'desc_tip'    => true,
                'default'     => __( 'Gift cards cannot be paid with monthly installments', ALMA_WC_TEXT_DOMAIN ),
            ),
        );

		$debug_fields = array(
			'debug_section' => array(
				'title' => __( '→ Debug options', ALMA_WC_TEXT_DOMAIN ),
				'type'  => 'title'
			),
			'debug'         => array(
				'title'       => __( 'Debug mode', ALMA_WC_TEXT_DOMAIN ),
				'type'        => 'checkbox',
				'label'       => __( 'Activate debug mode', ALMA_WC_TEXT_DOMAIN ) . sprintf( __( ' (<a href="%s">Go to logs</a>)', ALMA_WC_TEXT_DOMAIN ), alma_wc_plugin()->get_admin_logs_url() ),
				'description' => __( 'Enable logging info and errors to help debug any issue with the plugin', ALMA_WC_TEXT_DOMAIN ),
				'desc_tip'    => true,
				'default'     => 'no',
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
        if ( is_admin() ) {
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

		if ( array_key_exists('excluded_products_list', $this->settings) && count($this->settings['excluded_products_list']) > 0 ) {
            foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
                $product = $cart_item['data'];

                foreach ($this->settings['excluded_products_list'] as $category_slug ) {
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

	public function process_payment( $order_id ) {
		$error_msg = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', ALMA_WC_TEXT_DOMAIN );

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			wc_add_notice( $error_msg, 'error' );

			return array(
				'result' => 'error'
			);
		}

		try {
			$payment = $alma->payments->createPayment( Alma_WC_Payment::from_order( $order_id ) );
		} catch ( \Alma\API\RequestError $e ) {
			$this->logger->error( 'Error while creating payment: ' . $e->getMessage() );
			wc_add_notice( $error_msg, 'error' );

			return array( 'result' => 'error' );
		}

		// Redirect user to our payment page
		return array(
			'result'   => 'success',
			'redirect' => $payment->url
		);
	}

	private function _redirect_to_cart_with_error( $error_msg ) {
		wc_add_notice( $error_msg, 'error' );
		wp_redirect( wc_get_cart_url() );

		return array( 'result' => 'error', 'redirect' => wc_get_cart_url() );
	}

	public function validate_payment_on_customer_return( $payment_id ) {
        try {
            $order = Alma_WC_Payment_Validator::validate_payment( $payment_id );
        } catch ( AlmaPaymentValidationError $e ) {
            $error_msg = __( 'There was an error when validating your payment.<br>Please try again or contact us if the problem persists.', ALMA_WC_TEXT_DOMAIN );
            return $this->_redirect_to_cart_with_error( $error_msg );
        } catch ( \Exception $e ) {
            return $this->_redirect_to_cart_with_error( $e->getMessage() );
        }

		// Redirect user to the order confirmation page
		$return_url = $this->get_return_url( $order->get_wc_order() );
		wp_redirect( $return_url );

		return array( 'result' => 'success', 'redirect' => $return_url );
	}

    public function validate_payment_from_ipn( $payment_id )
    {
        try {
            Alma_WC_Payment_Validator::validate_payment( $payment_id );
        } catch ( \Exception $e ) {
            status_header( 500 );
            wp_send_json( array( 'error' => $e->getMessage() ) );
        }

        wp_send_json( array( 'success' => true ) );
	}

    private function product_categories_options()
    {
        $orderby = 'name';
        $order = 'asc';
        $hide_empty = false;
        $cat_args = array(
            'orderby'    => $orderby,
            'order'      => $order,
            'hide_empty' => $hide_empty,
        );

        $product_categories = get_terms( 'product_cat', $cat_args );

        $options = array();
        if ( ! empty( $product_categories ) ) {
            foreach ( $product_categories as $key => $category ) {
                $options[$category->slug] = $category->name;
            }
        }

        return $options;
    }
}
