<?php
/**
 * Alma date helper
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Date_Helper
 */
class Alma_WC_Date_Helper {

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger = new Alma_WC_Logger();
	}

	/**
	 * Gets dates in an interval.
	 *
	 * @param string $share_of_checkout_enabled_date The share of checkout enabled date by the merchant.
	 * @param string $last_update_date The last update date in timestamp format.
	 * @return array
	 */
	public function get_dates_in_interval( $share_of_checkout_enabled_date, $last_update_date ) {
		$start_date      = $last_update_date;
		$start_timestamp = $this->generate_timestamp( $start_date );
		if ( $last_update_date < $share_of_checkout_enabled_date ) {
			$start_date      = $share_of_checkout_enabled_date;
			$start_timestamp = $this->generate_timestamp( $start_date );
		}

		$dates_in_interval = array();
		for ( $i = $start_timestamp; time() - $i > 86400; $i = strtotime( '+1 day', $i ) ) {
			$dates_in_interval[] = gmdate( 'Y-m-d', $i );
		}

		return $dates_in_interval;
	}


	/**
	 * Generate a timestamp from a date formatted as "yyyy-mm-dd".
	 *
	 * @param string $date_yyyy_mm_dd A date.
	 * @return false|int
	 */
	private function generate_timestamp( $date_yyyy_mm_dd ) {
		$year  = substr( $date_yyyy_mm_dd, 0, 4 );
		$month = substr( $date_yyyy_mm_dd, 5, 2 );
		$day   = substr( $date_yyyy_mm_dd, 8, 2 );
		return mktime( 0, 0, 0, $month, $day, $year );
	}

}
