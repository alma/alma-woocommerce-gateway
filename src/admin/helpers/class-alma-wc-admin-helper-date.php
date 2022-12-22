<?php
/**
 * Alma_WC_Admin_Helper_Date.
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/admin/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Admin_Helper_Date
 */
class Alma_WC_Admin_Helper_Date {

	/**
	 * Gets dates in an interval until yesterday.
	 *
	 * @param string $start_date The share of checkout enabled date by the merchant formatted as yyyy-mm-dd.
	 *
	 * @param string $format The format.
	 *
	 * @return array
	 * @throws Exception Datetime exceptions.
	 */
	public static function get_dates_in_interval( $start_date, $format = 'Y-m-d' ) {
		// Declare an empty array.
		$dates_in_interval = array();

		// Variable that store the date interval of period 1 day.
		$interval = new DateInterval( 'P1D' );

		$real_end = new DateTime();
		$real_end->modify( '-1 day' );
		$period = new DatePeriod( new DateTime( $start_date ), $interval, $real_end );

		// Use loop to store date into array.
		foreach ( $period as $date ) {
			$dates_in_interval[] = $date->format( $format );
		}

		// Return the array elements.
		return $dates_in_interval;
	}
}
