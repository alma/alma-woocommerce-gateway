<?php

namespace Alma\Woocommerce\Services;

use Alma\API\DependenciesError;
use Alma\API\Endpoints\Merchants;
use Alma\API\Entities\DTO\MerchantBusinessEvent\CartInitiatedBusinessEvent;
use Alma\API\Entities\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEvent;
use Alma\API\Exceptions\ParametersException;
use Alma\API\Exceptions\RequestException;
use Alma\API\ParamsError;
use Alma\PrestaShop\Helpers\ConfigurationHelper;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Exceptions\AlmaBusinessEventException;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Gateways\Inpage\PayNowGateway as InPagePayNowGateway;
use Alma\Woocommerce\Gateways\Standard\PayNowGateway;

class AlmaBusinessEventService {
	const ALMA_BUSINESS_DATA = 'alma_business_data';

	/**
	 * @var \Alma\Woocommerce\AlmaLogger|mixed|null
	 */
	private $logger;

	const ALMA_CART_ID = 'alma_cart_id';
	/**
	 * @var AlmaSettings
	 */
	private $alma_settings;


	public function __construct( $logger = null ) {
		//This feature works only for WooCommerce > 3.6.0
		$version_factory = new VersionFactory();
		if ( version_compare( $version_factory->get_version(), '3.6', '<' ) ) {
			return;
		}
		if ( is_null( $logger ) ) {
			$logger = new AlmaLogger();
		}
		$this->logger = $logger;
		$this->alma_settings = new AlmaSettings();
		add_action( 'woocommerce_add_to_cart', [$this, 'on_cart_initiated'], 10, 6 );
		add_action('woocommerce_order_status_changed', [$this, 'on_order_status_changed'], 10, 4);
	}

	/**
	 * @param $cart_id
	 * @param $product_id
	 * @param $request_quantity
	 * @param $variation_id
	 * @param $variation
	 * @param $cart_item_data
	 *
	 * @return void
	 * @throws AlmaBusinessEventException
	 */
	public function on_cart_initiated( $cart_id, $product_id, $request_quantity, $variation_id, $variation, $cart_item_data ) {

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
				$this->logger->warning( '[Alma] Wrong Parameter Cart initiated Business event', [$e->getMessage()] );
			} catch ( RequestException $e ) {
				$this->logger->warning( '[Alma] Cart initiated Business event not sent', [$e->getMessage()] );
			} catch ( DependenciesError $e ) {
				$this->logger->warning( '[Alma] Alma Client DependenciesError', [$e->getMessage()] );
			} catch ( ParamsError $e ) {
				$this->logger->warning( '[Alma] Alma Client ParamsError', [$e->getMessage()] );
			} catch ( AlmaException $e ) {
				$this->logger->warning( '[Alma] Alma Client AlmaException', [$e->getMessage()] );
			}
		}
	}

	/**
	 * @param string $orderId
	 * @param $from
	 * @param $to
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function on_order_status_changed($orderId, $from, $to, $order) {
		global $wpdb;
		if (WC()->cart && $from == 'pending' && in_array($to, array_merge(wc_get_is_paid_statuses(), ['on-hold'])) && $order->get_cart_hash() == WC()->cart->get_cart_hash()) {
			$cart_id = WC()->session->get( self::ALMA_CART_ID, null );
			if (empty($cart_id)) {
				return;
			}

			$is_pay_now = false;
			$is_bnpl = false;
			$payment_id = null;
			if (strpos($order->get_payment_method(), 'alma') !== false) {
				$is_pay_now = in_array($order->get_payment_method(), [InPagePayNowGateway::GATEWAY_ID, PayNowGateway::GATEWAY_ID]);
				$is_bnpl = !$is_pay_now;
				$payment_id = $order->get_transaction_id();
			}

			$wpdb->update(
				$wpdb->prefix . self::ALMA_BUSINESS_DATA,
				[
					'order_id' => $orderId
				],
				[ 'cart_id' => $cart_id ],
				[
					'order_id' => '%d'
				],
				[ 'cart_id' => '%d' ]
			);

			$is_bnpl_eligible = $wpdb->get_row( $wpdb->prepare('SELECT is_bnpl_eligible FROM '.$wpdb->prefix . self::ALMA_BUSINESS_DATA . ' WHERE cart_id=%d', $cart_id) );

			try {
				$order_confirmed_business_event = new OrderConfirmedBusinessEvent(
					$is_pay_now,
					$is_bnpl,
					(bool) $is_bnpl_eligible,
					$orderId,
					$cart_id,
					$payment_id
				);
				$this->alma_settings->get_alma_client();
				$this->alma_settings->alma_client->merchants->sendOrderConfirmedBusinessEvent( $order_confirmed_business_event );
			} catch ( ParametersException $e ) {
				$this->logger->warning( '[Alma] Wrong Parameter Order Confirmed Business event', [$e->getMessage()] );
			} catch ( RequestException $e ) {
				$this->logger->warning( '[Alma] Order Confirmed Business event not sent', [$e->getMessage()] );
			} catch ( DependenciesError $e ) {
				$this->logger->warning( '[Alma] Alma Client DependenciesError', [$e->getMessage()] );
			} catch ( ParamsError $e ) {
				$this->logger->warning( '[Alma] Alma Client ParamsError', [$e->getMessage()] );
			} catch ( AlmaException $e ) {
				$this->logger->warning( '[Alma] Alma Client AlmaException', [$e->getMessage()] );
			}

		}
	}

	/**
	 * @param $cart_id
	 *
	 * @return bool
	 */
	private function is_cart_valid( $cart_id ) {
		global $wpdb;
		$result = $wpdb->get_row( $wpdb->prepare('SELECT order_id FROM '.$wpdb->prefix . self::ALMA_BUSINESS_DATA . ' WHERE cart_id=%d', $cart_id) );
		if (!$result) {
			//  No cart id found
			return false;
		}
		//
		if ($result->order_id != null) {
			// Cart id already converted
			return false;
		}
		return true;
	}

	/**
	 * @throws AlmaBusinessEventException
	 */
	private function save_cart() {
		global $wpdb;

		do {
			$cart_id = $this->generate_unique_bigint();
			$found_cart_id = $wpdb->get_col('SELECT cart_id FROM '.$wpdb->prefix . self::ALMA_BUSINESS_DATA . ' WHERE cart_id='.((int) $cart_id));
		} while ($found_cart_id);

		$result = $wpdb->insert(
			$wpdb->prefix . self::ALMA_BUSINESS_DATA,
			[
				'cart_id' => $cart_id,
			],
			[
				'cart_id' => '%d',
			]
		);

		if (!$result) {
			throw new AlmaBusinessEventException( __('Cart could not be created', 'alma-gateway-for-woocommerce') );
		}

		return $cart_id;
	}

	private function generate_unique_bigint() {
		// Get current timestamp (milliseconds)
		$timestamp = round(microtime(true) * 1000);

		// Add random component (5 digits)
		$random = mt_rand(10000, 99999); // NO SONAR

		// Combine timestamp + random to ensure uniqueness
		// Format: TTTTTTTTTTTTTRRRR
		$id = $timestamp . $random;

		// Ensure it fits in BIGINT(20) unsigned max value
		$max_bigint = '18446744073709551615';
		if (strlen($id) > strlen($max_bigint)) {
			$id = substr($id, 0, strlen($max_bigint));
		}

		return $id;
	}

	/**
	 * @param $eligibility
	 *
	 * @return void
	 */
	public function save_eligibility($eligibility) {
		global $wpdb;
		$cart_id = WC()->session->get( self::ALMA_CART_ID, null );
		if (empty($cart_id)) {
			return;
		}
		$isEligible = 0;
		$planKeys = [];
		/*
		 * @var \Alma\Woocommerce\Eligibility $plan
		 */
		foreach ($eligibility as $planKey => $plan) {
			if ($plan->isEligible) {
				$planKeys[] = $planKey;
			}
		}
		$planKeysWithoutPayNow = array_filter($planKeys, function ($key) {
			return $key !== 'general_1_0_0';
		});

		if (count($planKeysWithoutPayNow) > 0) {
			$isEligible = 1;
		}

		$wpdb->update(
			$wpdb->prefix . self::ALMA_BUSINESS_DATA,
			[
				'is_bnpl_eligible' => $isEligible
			],
			[ 'cart_id' => $cart_id ],
			[
				'is_bnpl_eligible' => '%d'
			],
			[ 'cart_id' => '%d' ]
		);
	}

}