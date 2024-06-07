<?php
/**
 * CartHelper.
 *
 * @since 4.3.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\SessionFactory;
use Alma\Woocommerce\Factories\VersionFactory;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CartHelper.
 */
class CartHelper {

	/**
	 * Helper global.
	 *
	 * @var ToolsHelper
	 */
	protected $tools_helper;

	/**
	 * Helper Session.
	 *
	 * @var SessionFactory
	 */
	protected $session_factory;


	/**
	 * Factory Version.
	 *
	 * @var VersionFactory
	 */
	protected $version_factory;

	/**
	 * Factory Cart.
	 *
	 * @var CartFactory
	 */
	protected $cart_factory;


	/**
	 * Alma Settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;


	/**
	 * Alma Logger.
	 *
	 * @var AlmaLogger
	 */
	protected $alma_logger;

	/**
	 * Eligibilities;
	 *
	 * @var array|null
	 */
	protected $eligibilities;

	/**
	 * Customer helper.
	 *
	 * @var CustomerHelper
	 */
	protected $customer_helper;

	/**
	 * Constructor.
	 *
	 * @param ToolsHelper    $tools_helper The tool Helper.
	 * @param SessionFactory $session_factory The session Helper.
	 * @param VersionFactory $version_factory The version Helper.
	 * @param CartFactory    $cart_factory The cart Helper.
	 * @param AlmaSettings   $alma_settings The alma settings.
	 * @param AlmaLogger     $alma_logger The alma logger.
	 * @param CustomerHelper $customer_helper The customer helper.
	 */
	public function __construct( $tools_helper, $session_factory, $version_factory, $cart_factory, $alma_settings, $alma_logger, $customer_helper ) {
		$this->tools_helper    = $tools_helper;
		$this->session_factory = $session_factory;
		$this->version_factory = $version_factory;
		$this->cart_factory    = $cart_factory;
		$this->alma_settings   = $alma_settings;
		$this->alma_logger     = $alma_logger;
		$this->customer_helper = $customer_helper;
	}

	/**
	 * Get cart total in cents.
	 *
	 * @return integer
	 * @see alma_price_to_cents()
	 * @see get_total_from_cart
	 */
	public function get_total_in_cents() {
		return $this->tools_helper->alma_price_to_cents( $this->get_total_from_cart() );
	}

	/**
	 * Gets total from wc cart depending on which wc version is running.
	 *
	 * @return float
	 */
	public function get_total_from_cart() {
		$cart = $this->cart_factory->get_cart();

		if ( ! $cart ) {
			return 0;
		}

		if ( version_compare( $this->version_factory->get_version(), '3.2.0', '<' ) ) {
			return $cart->total;
		}

		$total = $cart->get_total( null );

		$session       = $this->session_factory->get_session();
		$session_total = $session->get( 'cart_totals', null );

		if (
			(
				0 === $total
				|| '0' === $total
			)
			&& ! empty( $session_total['total'] )
		) {
			$total = $session_total['total'];
		}

		return $total;
	}

	/**
	 * Get eligibilities from cart.
	 *
	 * @return Eligibility[]|array
	 */
	public function get_cart_eligibilities() {

		$amount = $this->get_total_in_cents();

		if ( 0 === $amount ) {
			return array();
		}

		if ( ! $this->eligibilities ) {

			try {
				$this->alma_settings->get_alma_client();
				$payload             = $this->get_eligibility_payload_from_cart();
				$this->eligibilities = $this->alma_settings->alma_client->payments->eligibility( $payload );
			} catch ( \Exception $error ) {
				$this->alma_logger->error( $error->getMessage(), $error->getTrace() );

				return array();
			}

			if ( ! is_array( $this->eligibilities ) ) {
				$this->alma_logger->error( 'Eligibilities must be an array' );

				return array();
			}
		}

		return $this->eligibilities;
	}

	/**
	 * Get eligible plans keys for current cart.
	 *
	 * @param array|Eligibility[] $cart_eligibilities The eligibilities.
	 * @return array
	 */
	public function get_eligible_plans_keys_for_cart( $cart_eligibilities = array() ) {
		if ( empty( $cart_eligibilities ) ) {
			$cart_eligibilities = $this->get_cart_eligibilities();
		}

		$eligibilities = array_filter(
			$this->alma_settings->get_eligible_plans_keys( $this->get_total_in_cents() ),
			function ( $key ) use ( $cart_eligibilities ) {
				if ( is_array( $cart_eligibilities ) ) {
					return array_key_exists( $key, $cart_eligibilities );
				}

				return property_exists( $cart_eligibilities, $key );
			}
		);

		return $eligibilities;
	}

	/**
	 * Get Eligibility / Payment formatted eligible plans definitions for current cart.
	 *
	 * @return array<array>
	 */
	public function get_eligible_plans_for_cart() {
		$amount = $this->get_total_in_cents();

		return array_values(
			array_map(
				function ( $plan ) use ( $amount ) {
					unset( $plan['max_amount'] );
					unset( $plan['min_amount'] );
					if ( isset( $plan['deferred_months'] ) && 0 === $plan['deferred_months'] ) {
						unset( $plan['deferred_months'] );
					}
					if ( isset( $plan['deferred_days'] ) && 0 === $plan['deferred_days'] ) {
						unset( $plan['deferred_days'] );
					}

					return $plan;
				},
				$this->alma_settings->get_eligible_plans_definitions( $amount )
			)
		);
	}

	/**
	 * Create Eligibility data for Alma API request from WooCommerce Cart.
	 *
	 * @return array Payload to request eligibility v2 endpoint.
	 */
	public function get_eligibility_payload_from_cart() {

		$data = array(
			'purchase_amount' => $this->get_total_in_cents(),
			'queries'         => $this->get_eligible_plans_for_cart(),
			'locale'          => apply_filters( 'alma_eligibility_user_locale', get_locale() ),
		);

		$billing_country  = $this->customer_helper->get_billing_address()['country'];
		$shipping_country = $this->customer_helper->get_shipping_address()['country'];

		if ( $billing_country ) {
			$data['billing_address'] = array( 'country' => $billing_country );
		}
		if ( $shipping_country ) {
			$data['shipping_address'] = array( 'country' => $shipping_country );
		}

		return $data;
	}
}
