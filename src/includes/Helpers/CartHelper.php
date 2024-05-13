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
	 * Constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param ToolsHelper    $tools_helper The tool Helper.
	 * @param SessionFactory $session_factory The session Helper.
	 * @param VersionFactory $version_factory The version Helper.
	 * @param CartFactory    $cart_factory The cart Helper.
	 */
	public function __construct( $tools_helper, $session_factory, $version_factory, $cart_factory ) {
		$this->tools_helper    = $tools_helper;
		$this->session_factory = $session_factory;
		$this->version_factory = $version_factory;
		$this->cart_factory    = $cart_factory;
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
	 * @return Eligibility|Eligibility[]|array
	 */
	public function get_cart_eligibilities() {

		$amount = $this->get_total_in_cents();

		$alma_settings  = new AlmaSettings();
		$logger         = new AlmaLogger();
		$payment_helper = new PaymentHelper();

		if ( 0 === $amount ) {
			return array();
		}

		if ( ! $this->eligibilities ) {

			try {
				$alma_settings->get_alma_client();
				$payload             = $payment_helper->get_eligibility_payload_from_cart();
				$this->eligibilities = $alma_settings->alma_client->payments->eligibility( $payload );
			} catch ( \Exception $error ) {
				$logger->error( $error->getMessage(), $error->getTrace() );

				return array();
			}
		}

		return $this->eligibilities;
	}

	/**
	 * Get eligible plans keys for current cart.
	 *
	 * @param array       $cart_eligibilities The eligibilities.
	 * @param string|null $gateway_id The gateway id.
	 * @return array
	 */
	public function get_eligible_plans_keys_for_cart( $cart_eligibilities = array(), $gateway_id = null ) {
		$alma_plan_builder = new PlanBuilderHelper();
		$alma_settings     = new AlmaSettings();

		if ( empty( $cart_eligibilities ) ) {
			$cart_eligibilities = $this->get_cart_eligibilities();
		}

		$eligibilities = array_filter(
			$alma_settings->get_eligible_plans_keys( $this->get_total_in_cents() ),
			function ( $key ) use ( $cart_eligibilities ) {
				if ( is_array( $cart_eligibilities ) ) {
					return array_key_exists( $key, $cart_eligibilities );
				}

				return property_exists( $cart_eligibilities, $key );
			}
		);

		return $alma_plan_builder->order_plans( $eligibilities, $gateway_id );
	}

	/**
	 * Get Eligibility / Payment formatted eligible plans definitions for current cart.
	 *
	 * @return array<array>
	 */
	public function get_eligible_plans_for_cart() {
		$alma_settings = new AlmaSettings();
		$amount        = $this->get_total_in_cents();

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
				$alma_settings->get_eligible_plans_definitions( $amount )
			)
		);
	}

}
