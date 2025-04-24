<?php

namespace Alma\Woocommerce\Services;

use Alma\API\DependenciesError;
use Alma\API\Endpoints\Results\Eligibility;
use Alma\API\Entities\DTO\MerchantBusinessEvent\CartInitiatedBusinessEvent;
use Alma\API\Entities\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEvent;
use Alma\API\Exceptions\ParametersException;
use Alma\API\Exceptions\RequestException;
use Alma\API\ParamsError;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Gateways\Inpage\PayNowGateway as InPagePayNowGateway;
use Alma\Woocommerce\Gateways\Standard\PayNowGateway;

class AlmaBusinessEventService {
	const ALMA_BUSINESS_DATA = 'alma_business_data';
	const ALMA_CART_ID       = 'alma_cart_id';
	/**
	 * @var AlmaLogger|mixed|null
	 */
	private $logger;
	/**
	 * @var AlmaSettings
	 */
	private $alma_settings;
	/**
	 * @var string
	 */
	private $alma_business_data_table;


	public function __construct( $logger = null ) {
		global $wpdb;
		//This feature works only for WooCommerce > 3.6.0
		$version_factory = new VersionFactory();
		if ( version_compare( $version_factory->get_version(), '3.6', '<' ) ) {
			return;
		}
		if ( is_null( $logger ) ) {
			$logger = new AlmaLogger();
		}
		$this->logger                   = $logger;
		$this->alma_settings            = new AlmaSettings();
		$this->alma_business_data_table = esc_sql( $wpdb->prefix . self::ALMA_BUSINESS_DATA );
	}

	/**
	 * Event launched when a cart is initiated.
	 *
	 * @return void
	 */
	public function on_cart_initiated() {

		//  Get cart id on session
		$alma_cart_id = WC()->session->get( self::ALMA_CART_ID, null );

		//  Check if cart id is valid on database
		if ( empty( $alma_cart_id ) || ! $this->is_cart_valid( $alma_cart_id ) ) {
			$alma_cart_id = $this->save_cart();
			WC()->session->set( self::ALMA_CART_ID, $alma_cart_id );
			try {
				$cart_initiated_business_event = new CartInitiatedBusinessEvent( $alma_cart_id );
				$this->alma_settings->get_alma_client();
				$this->alma_settings->alma_client->merchants->sendCartInitiatedBusinessEvent( $cart_initiated_business_event );
			} catch ( ParametersException $e ) {
				$this->logger->warning( '[Alma] Wrong Parameter Cart initiated Business event', array( $e->getMessage() ) );
			} catch ( RequestException $e ) {
				$this->logger->warning( '[Alma] Cart initiated Business event not sent', array( $e->getMessage() ) );
			} catch ( DependenciesError $e ) {
				$this->logger->warning( '[Alma] Alma Client DependenciesError', array( $e->getMessage() ) );
			} catch ( ParamsError $e ) {
				$this->logger->warning( '[Alma] Alma Client ParamsError', array( $e->getMessage() ) );
			} catch ( AlmaException $e ) {
				$this->logger->warning( '[Alma] Alma Client AlmaException', array( $e->getMessage() ) );
			}
		}
	}

	/**
	 * @param $order_id
	 *
	 * @return void
	 */
	public function on_create_order( $order_id ) {
		global $wpdb;
		$alma_cart_id = WC()->session->get( self::ALMA_CART_ID, null );

		//  Check if cart id is valid on database
		if ( empty( $alma_cart_id ) ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . self::ALMA_BUSINESS_DATA,
			array(
				'order_id' => $order_id,
			),
			array( 'cart_id' => $alma_cart_id ),
			array(
				'order_id' => '%d',
			),
			array( 'cart_id' => '%d' )
		);
	}

	/**
	 * Event launched when an order status is changed.
	 *
	 * @param           $order_id
	 * @param           $from
	 * @param           $to
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function on_order_status_changed( $order_id, $from, $to, $order ) {

		global $wpdb;
		$alma_business_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->alma_business_data_table WHERE order_id=%d", $order_id ) );
		if ( ! $alma_business_data ) {
			//  No cart id found
			return;
		}

		if ( 'pending' === $from && in_array( $to, array_merge( wc_get_is_paid_statuses(), array( 'on-hold' ) ) ) ) {
			$is_pay_now = false;
			$is_bnpl    = false;
			$payment_id = null;
			if ( strpos( $order->get_payment_method(), 'alma' ) !== false ) {
				$is_pay_now = in_array(
					$order->get_payment_method(),
					array(
						InPagePayNowGateway::GATEWAY_ID,
						PayNowGateway::GATEWAY_ID,
					)
				);
				$is_bnpl    = ! $is_pay_now;
				$payment_id = $alma_business_data->alma_payment_id;
			}

			try {
				$order_confirmed_business_event = new OrderConfirmedBusinessEvent(
					$is_pay_now,
					$is_bnpl,
					(bool) $alma_business_data->is_bnpl_eligible,
					(string) $order_id,
					$alma_business_data->cart_id,
					$payment_id
				);
				$this->alma_settings->get_alma_client();
				$this->alma_settings->alma_client->merchants->sendOrderConfirmedBusinessEvent( $order_confirmed_business_event );
			} catch ( ParametersException $e ) {
				$this->logger->warning( '[Alma] Wrong Parameter Order Confirmed Business event', array( $e->getMessage() ) );
			} catch ( RequestException $e ) {
				$this->logger->warning( '[Alma] Order Confirmed Business event not sent', array( $e->getMessage() ) );
			} catch ( DependenciesError $e ) {
				$this->logger->warning( '[Alma] Alma Client DependenciesError', array( $e->getMessage() ) );
			} catch ( ParamsError $e ) {
				$this->logger->warning( '[Alma] Alma Client ParamsError', array( $e->getMessage() ) );
			} catch ( AlmaException $e ) {
				$this->logger->warning( '[Alma] Alma Client AlmaException', array( $e->getMessage() ) );
			}

		}
	}

	/**
	 * @param $eligibility
	 *
	 * @return void
	 */
	public function save_eligibility( $eligibility ) {
		global $wpdb;
		$cart_id = WC()->session->get( self::ALMA_CART_ID, null );
		if ( empty( $cart_id ) ) {
			return;
		}
		$is_eligible = 0;
		$plan_keys   = array();

		/**
		 * @var Eligibility $plan
		 */
		foreach ( $eligibility as $plan_key => $plan ) {
			if ( $plan->isEligible() ) {
				$plan_keys[] = $plan_key;
			}
		}
		$plan_keys_without_pay_now = array_filter(
			$plan_keys,
			function ( $key ) {
				return 'general_1_0_0' !== $key;
			}
		);

		if ( count( $plan_keys_without_pay_now ) > 0 ) {
			$is_eligible = 1;
		}

		$wpdb->update(
			$wpdb->prefix . self::ALMA_BUSINESS_DATA,
			array(
				'is_bnpl_eligible' => $is_eligible,
			),
			array( 'cart_id' => $cart_id ),
			array(
				'is_bnpl_eligible' => '%d',
			),
			array( 'cart_id' => '%d' )
		);
	}

	/**
	 * @param $payment_id
	 *
	 * @return void
	 */
	public function save_payment_id( $payment_id ) {
		global $wpdb;
		$cart_id = WC()->session->get( self::ALMA_CART_ID, null );
		if ( empty( $cart_id ) ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . self::ALMA_BUSINESS_DATA,
			array(
				'alma_payment_id' => $payment_id,
			),
			array( 'cart_id' => $cart_id ),
			array(
				'alma_payment_id' => '%s',
			),
			array( 'cart_id' => '%d' )
		);
	}

	/**
	 * Add hooks to the class.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'woocommerce_add_to_cart', array( $this, 'on_cart_initiated' ), 10, 6 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'on_create_order' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );
	}

	/**
	 * Check if cart id is valid.
	 *
	 * @param $cart_id
	 *
	 * @return bool
	 */
	private function is_cart_valid( $cart_id ) {
		global $wpdb;
		$alma_business_data = $wpdb->get_row( $wpdb->prepare( "SELECT order_id FROM $this->alma_business_data_table WHERE cart_id=%d", $cart_id ) );
		if ( ! $alma_business_data ) {
			//  No cart id found
			return false;
		}
		//
		if ( null !== $alma_business_data->order_id ) {
			// Cart id already converted
			return false;
		}

		return true;
	}

	/**
	 * Save cart id in database.
	 *
	 * @return string
	 */
	private function save_cart() {
		global $wpdb;

		do {
			$tools_helper_builder = new ToolsHelperBuilder();
			$tools_helper         = $tools_helper_builder->get_instance();
			$cart_id              = $tools_helper->generate_unique_bigint();
			$found_cart_id        = $wpdb->get_col( $wpdb->prepare( "SELECT cart_id FROM $this->alma_business_data_table WHERE cart_id=%d", $cart_id ) );
		} while ( $found_cart_id );

		$result = $wpdb->insert(
			$wpdb->prefix . self::ALMA_BUSINESS_DATA,
			array(
				'cart_id' => $cart_id,
			),
			array(
				'cart_id' => '%d',
			)
		);

		if ( ! $result ) {
			$this->logger->warning( '[Alma] Database Insert Error, Cart could not be created', array( $wpdb->last_error ) );
		}

		return $cart_id;
	}
}
