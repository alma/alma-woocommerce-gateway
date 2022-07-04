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
	 * Gets dates in an interval until yesterday.
	 *
	 * @param string $start_date The share of checkout enabled date by the merchant formatted as yyyy-mm-dd.
	 *
	 * @return array
	 */
	public function get_dates_in_interval( $start_date ) {
		$start_timestamp = strtotime( $start_date );

		$dates_in_interval = array();
		for ( $i = $start_timestamp; time() - $i > 86400; $i = strtotime( '+1 day', $i ) ) {
			$dates_in_interval[] = gmdate( 'Y-m-d', $i );
		}

		return $dates_in_interval;
	}

}
