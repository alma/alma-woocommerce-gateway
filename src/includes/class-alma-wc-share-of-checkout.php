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

	const CRON_ACTION = 'share_of_checkout_cron_action';

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
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
	}

	/**
	 * Add our cron schedule in WP schedules list.
	 */
	public function add_cron_schedule() {

		$schedules['alma_once_a_day'] = array(
			'interval' => 20,
			'display'  => esc_html__( 'Every day for alma' ),
		);

		return $schedules;
	}

	/**
	 * Bootstrap function.
	 */
	public function bootstrap() {

		if ( isset( $_GET['test_soc'] ) ) {
			$result = $this->share_of_checkout_helper->get_payload( '2022-01-01', '2022-03-30' );
			echo '<pre>';
			print_r( $result );
			echo '</pre>';
			exit;
		}

		// $this->share_days();
		//
		// $timestamp = wp_next_scheduled( 'cron_share_of_checkout_action' );
		// wp_unschedule_event( $timestamp, 'cron_share_of_checkout_action' );
		//
		// $timestamp = wp_next_scheduled( 'alma_cron_my_test_action' );
		// wp_unschedule_event( $timestamp, 'alma_cron_my_test_action' );
		// return;

		if ( ! wp_next_scheduled( self::CRON_ACTION ) ) {
			error_log( 'wp_schedule_event()' );
			wp_schedule_event( $this->get_cron_job_first_occurrence_timestamp(), 'alma_once_a_day', self::CRON_ACTION );
		}
		add_action( self::CRON_ACTION, array( $this, 'share_of_checkout_cron_execution_callback' ) );
	}

	/**
	 * Returns the timestamp of the first occurrence of the cron job.
	 *
	 * @return int A timestamp
	 */
	function get_cron_job_first_occurrence_timestamp() {
		// @todo to implement.
		return time();
	}

	/**
	 *
	 */
	public function share_of_checkout_cron_execution_callback() {
		error_log( '----- Alma_WC_Share_Of_Checkout::alma_exec_cron() -----' );
		// @todo here we should call $this->share_days();
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return void
	 * @throws \Alma\API\RequestError
	 */
	public function share_days() {

		// ini_set('max_execution_time', 30);

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return;
		}

		if ( 'yes' !== alma_wc_plugin()->settings->share_of_checkout_enabled ) {
			// error_log( 'Share Of Checkout is not enabled' );
			$this->logger->info( 'Share Of Checkout is not enabled' );
			return;
		}
		$share_of_checkout_enabled_date = alma_wc_plugin()->settings->share_of_checkout_enabled_date;
		// error_log( '$share_of_checkout_enabled_date = ' . $share_of_checkout_enabled_date );

		$last_update_date = $this->share_of_checkout_helper->get_last_update_date();
		// error_log( '$last_update_date = ' . $last_update_date );

		$dates_to_share = $this->date_helper->get_dates_in_interval( $last_update_date, $share_of_checkout_enabled_date );
		// error_log( serialize( $dates_to_share ) );

		foreach ( $dates_to_share as $date ) {
			try {
				$this->share_of_checkout_helper->set_share_of_checkout_from_date( $date );
				$this->share_of_checkout_helper->share_day();
			} catch ( RequestError $e ) {
				// throw new RequestError($e->getMessage(), null, null);
			}
		}
	}
}

return;

// error_log( '----- tests -----' );
// tests
// https://developer.wordpress.org/plugins/cron/understanding-wp-cron-scheduling/
add_filter( 'cron_schedules', 'example_add_cron_interval' );
function example_add_cron_interval( $schedules ) {
	$schedules['five_seconds'] = array(
		'interval' => 5,
		'display'  => esc_html__( 'Every Five Seconds' ),
	);
	return $schedules;
}

if ( ! wp_next_scheduled( 'bl_cron_hook' ) ) {
	error_log( '----- bl_cron_hook -----' );
	wp_schedule_event( time(), 'five_seconds', 'bl_cron_hook' );
}

add_action( 'bl_cron_hook', 'bl_cron_exec' );

function bl_cron_exec() {
	error_log( '----- bl_cron_exec() -----' );
}





