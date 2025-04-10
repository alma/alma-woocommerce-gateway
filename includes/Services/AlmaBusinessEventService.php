<?php

namespace Alma\Woocommerce\Services;

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Exceptions\AlmaBusinessEventException;
use Alma\Woocommerce\Factories\VersionFactory;

class AlmaBusinessEventService {
	const ALMA_BUSINESS_DATA = 'alma_business_data';

	/**
	 * @var \Alma\Woocommerce\AlmaLogger|mixed|null
	 */
	private $logger;

	const ALMA_CART_ID = 'alma_cart_id';

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
//		if (WC()->cart->is_empty()) {
//			return;
//		}
		//  Get cart id on session
		$alma_cart_id = WC()->session->get( self::ALMA_CART_ID, null );

		//  Check if cart id is valid on database
		if ( empty( $alma_cart_id ) || ! $this->is_cart_valid( $alma_cart_id ) ) {
			$alma_cart_id = $this->save_cart();
			WC()->session->set( self::ALMA_CART_ID, $alma_cart_id );
		}
	}

	public function on_order_status_changed($orderId, $from, $to, $order) {
		global $wpdb;
		if (WC()->cart && $from == 'pending' && in_array($to, array_merge(wc_get_is_paid_statuses(), ['on-hold'])) && $order->get_cart_hash() == WC()->cart->get_cart_hash()) {
			$cart_id = WC()->session->get( self::ALMA_CART_ID, null );
			if (empty($cart_id)) {
				return;
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
}