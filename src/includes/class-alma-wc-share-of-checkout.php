<?php
/**
 * Alma share of checkout
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Share_Of_Checkout
 */
class Alma_WC_Share_Of_Checkout {

	const CRON_ACTION = 'alma_share_of_checkout_cron_action';

	/**
	 * Logger.
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger = new Alma_WC_Logger();
	}

	/**
	 * Init function.
	 */
	public function init() {
		add_action( 'init', array( $this, 'bootstrap' ) );
	}

	/**
	 * Bootstrap function.
	 *
	 * @return void
	 */
	public function bootstrap() {
		add_action( self::CRON_ACTION, array( $this, 'share_of_checkout_cron_execution_callback' ) );
		if ( ! wp_next_scheduled( self::CRON_ACTION ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_ACTION );
		}
	}

	/**
	 * Share of checkout cron execution callback.
	 *
	 * @return void
	 */
	public function share_of_checkout_cron_execution_callback() {
		$this->share_days();
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return void
	 */
	public function share_days() {
		if (
			alma_wc_plugin()->settings->is_test()
			|| 'yes' !== alma_wc_plugin()->settings->share_of_checkout_enabled
		) {
			return;
		}

		$share_of_checkout_enabled_date = alma_wc_plugin()->settings->share_of_checkout_enabled_date;
		try {
			$last_update_date = Alma_WC_Admin_Helper_Share_Of_Checkout::get_last_update_date();
		} catch ( \Exception $e ) {
			$this->logger->error( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
			$last_update_date = Alma_WC_Admin_Helper_Share_Of_Checkout::get_default_last_update_date();
		}
		$from_date      = max( $last_update_date, $share_of_checkout_enabled_date );
		$dates_to_share = Alma_WC_Admin_Helper_Date::get_dates_in_interval( $from_date );

		foreach ( $dates_to_share as $date ) {
			$this->share_day( $date );
		}
	}

	/**
	 * Send data for one day to API.
	 *
	 * @param string $start_time The start time.
	 *
	 * @return void
	 */
	public function share_day( $start_time ) {
		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return;
		}

		try {
			$alma->shareOfCheckout->share( Alma_WC_Admin_Helper_Share_Of_Checkout::get_payload( $start_time ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		} catch ( RequestError $e ) {
			$this->logger->error( sprintf( 'Alma_WC_Share_Of_Checkout_Helper::share error get message : %s', $e->getMessage() ) );
		}
	}

}
