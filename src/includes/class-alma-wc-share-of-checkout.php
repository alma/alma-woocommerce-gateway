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
		add_filter( 'cron_schedules', array( $this, 'add_schedule' ) );
	}

	/**
	 * Add the cron task to share datas.
	 */
	public function add_schedule() {

		error_log( 'add_schedule()' );

//		$schedules['alma_once_a_day'] = array(
//			'interval' => 86400,
//			'display'  => esc_html__( 'Every day for alma' ),
//		);
		$schedules['alma_every_ten_seconds_2'] = array(
			'interval' => 10,
			'display'  => esc_html__( 'Every 10 seconds for alma' ),
		);
		return $schedules;
	}

	/**
	 * Bootstrap function.
	 */
	public function bootstrap() {
		$this->share_days();

//		if ( ! wp_next_scheduled( 'alma_cron_hook' ) ) {
//			wp_schedule_event( time(), 'alma_once_a_day', 'alma_cron_hook' );
//		}

		error_log( "wp_next_scheduled( 'alma_cron_hook_test' )" );
		error_log( wp_next_scheduled( 'alma_cron_hook_test' ) );

		if ( ! wp_next_scheduled( 'alma_cron_hook_test' ) ) {
			error_log( 'wp_schedule_event()' );
			wp_schedule_event( time(), 'alma_every_ten_seconds_2', 'alma_cron_hook_test' );
		}
		add_action( 'alma_cron_hook_test', array( $this, 'alma_exec_cron' ) );
	}

	/**
	 *
	 */
	public function alma_exec_cron() {
		error_log( '----- Alma_WC_Share_Of_Checkout::alma_cron_hook_test() -----' );
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
//			error_log( 'Share Of Checkout is not enabled' );
			$this->logger->info( 'Share Of Checkout is not enabled' );
			return;
		}
		$share_of_checkout_enabled_date = alma_wc_plugin()->settings->share_of_checkout_enabled_date;
//		error_log( '$share_of_checkout_enabled_date = ' . $share_of_checkout_enabled_date );

		$last_update_date = $this->share_of_checkout_helper->get_last_update_date();
//		error_log( '$last_update_date = ' . $last_update_date );

		$dates_to_share = $this->date_helper->get_dates_in_interval( $last_update_date, $share_of_checkout_enabled_date );
//		error_log( serialize( $dates_to_share ) );

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










