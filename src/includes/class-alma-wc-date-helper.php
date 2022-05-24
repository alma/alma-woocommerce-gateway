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
	 * @param string $from The "from" date.
	 * @param string $share_of_checkout_enabled_date The share of checkout enabled date by the merchant.
	 * @param string $to The "to" date.
	 * @return array
	 */
	public function get_dates_in_interval( $from, $share_of_checkout_enabled_date, $to = null ) {
		if ( ! isset( $to ) ) {
			$to = strtotime( '-1 day' );
		}
		$dates_in_interval = array();
		$start_timestamp   = strtotime( '+1 day', strtotime( $from ) );
		for ( $i = $start_timestamp; $i <= $to; $i = strtotime( '+1 day', $i ) ) {
			if ( $i >= strtotime( $share_of_checkout_enabled_date ) ) {
				$dates_in_interval[] = date( 'Y-m-d', $i );
			}
		}
		return $dates_in_interval;
	}

}
