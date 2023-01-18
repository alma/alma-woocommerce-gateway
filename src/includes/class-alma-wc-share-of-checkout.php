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

	/**
	 * Logger.
	 *
	 * @var Alma_WC_Logger
	 */
	protected $logger;


	/**
	 * Db Settings.
	 *
	 * @var Alma_WC_Settings
	 */
	protected $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger   = new Alma_WC_Logger();
		$this->settings = new Alma_WC_Settings();
	}

	/**
	 * Share of checkout cron execution.
	 *
	 * @return void
	 */
	public function send_soc_data() {
		$settings_date = $this->settings->share_of_checkout_last_sharing_date;
		try {
			if (
				$this->settings->is_test()
				|| 'yes' !== $this->settings->share_of_checkout_enabled
			) {
				return;
			}

			$today = new DateTime();

			$last_sharing_date = null;

			if ( ! empty( $settings_date ) ) {
				$last_sharing_date = new DateTime( $settings_date );
			}

			if (
				empty( $settings_date )
				|| $today->format( 'Y-m-d' ) > $last_sharing_date->format( 'Y-m-d' )
			) {
				$this->settings->share_of_checkout_last_sharing_date = $today->format( 'Y-m-d' );
				$this->settings->save();

				$this->share_days();
			}
		} catch ( \Exception $e ) {
			$this->settings->share_of_checkout_last_sharing_date = $settings_date;
			$this->settings->save();

			$this->logger->error(
				sprintf( 'An error occured when sending soc data. Message "%s"', $e->getMessage() ),
				$e->getTrace()
			);
		}
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return void
	 */
	public function share_days() {
		$share_of_checkout_enabled_date = $this->settings->share_of_checkout_enabled_date;

		try {
			$last_update_date = Alma_WC_Admin_Helper_Share_Of_Checkout::get_last_update_date();
		} catch ( \Exception $e ) {
			$this->logger->error( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
			$last_update_date = Alma_WC_Admin_Helper_Share_Of_Checkout::get_default_last_update_date();
		}

		$from_date = max( $last_update_date, $share_of_checkout_enabled_date );

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
