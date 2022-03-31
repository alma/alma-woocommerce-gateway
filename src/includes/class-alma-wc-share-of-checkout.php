<?php
/**
 * Alma share of checkout
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Share_Of_Checkout
 */
class Alma_WC_Share_Of_Checkout {

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Logger
	 *
	 * @var Alma_WC_Share_Of_Checkout_Helper
	 */
	private $share_of_checkout_helper;

	/**
	 * Logger
	 *
	 * @var Alma_WC_Date_Helper
	 */
	private $date_helper;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger                   = new Alma_WC_Logger();
		$this->share_of_checkout_helper = new Alma_WC_Share_Of_Checkout_Helper();
		$this->date_helper              = new Alma_WC_Date_Helper();
	}

	/**
	 * Init function.
	 */
	public function init() {
		add_action( 'init', array( $this, 'bootstrap' ) );
	}

	/**
	 * Bootstrap function.
	 */
	public function bootstrap() {
		$this->share_days();
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return mixed
	 */
	public function share_days() {

		// ini_set('max_execution_time', 30);

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return;
		}

		if ( 'yes' !== alma_wc_plugin()->settings->share_of_checkout_enabled ) {
			error_log( 'Share Of Checkout is not enabled' );
			$this->logger->info( 'Share Of Checkout is not enabled' );
			return;
		}
		$share_of_checkout_enabled_date = alma_wc_plugin()->settings->share_of_checkout_enabled_date;
		error_log( '$share_of_checkout_enabled_date = ' . $share_of_checkout_enabled_date );

		$last_update_date = $this->share_of_checkout_helper->get_last_update_date();
		error_log( '$last_update_date = ' . $last_update_date );

		$dates_to_share = $this->date_helper->get_dates_in_interval( $last_update_date, $share_of_checkout_enabled_date );
		error_log( serialize( $dates_to_share ) );

		foreach ( $dates_to_share as $date ) {
			try {
				$this->share_of_checkout_helper->set_share_of_checkout_from_date( $date );
				$this->share_of_checkout_helper->share_day();
			} catch ( RequestError $e ) {
				// throw new RequestError($e->getMessage(), null, null);
			}
		}

		// $result = $this->get_payload( '2022-01-01', '2022-03-30' );
		// echo '<pre>';
		// print_r( $result );
		// echo '</pre>';
		// exit;
	}



}










