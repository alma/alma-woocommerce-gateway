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
	}

	/**
	 * Bootstrap function.
	 *
	 * @return void
	 */
	public function bootstrap() {

		// Debug test.
		if ( isset( $_GET['test_soc'] ) ) {
			$result = $this->share_of_checkout_helper->get_payload();
			echo '<pre>';
			print_r( $result );
			echo '</pre>';
			// echo 'DB_HOST = ' . DB_HOST . '<br/>';
			// echo 'DB_USER = ' . DB_USER . '<br/>';
			// echo 'DB_NAME = ' . DB_NAME . '<br/>';
			// echo 'DB_PASSWORD = ' . DB_PASSWORD . '<br/>';
			// echo '<pre>';
			// print_r( alma_wc_plugin()->settings );
			// echo '</pre>';

			$this->share_days();
			exit;
		}

		if ( ! wp_next_scheduled( self::CRON_ACTION ) ) {
			error_log( 'wp_schedule_event() : ' . self::CRON_ACTION );
			wp_schedule_event( time(), 'daily', self::CRON_ACTION );
		}
		add_action( self::CRON_ACTION, array( $this, 'share_of_checkout_cron_execution_callback' ) );
	}

	/**
	 * Share of checkout cron execution callback.
	 *
	 * @return void
	 */
	public function share_of_checkout_cron_execution_callback() {
		error_log( '----- function share_of_checkout_cron_execution_callback() -----' );
		// @todo here call $this->share_days();
		$this->share_days();
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return void
	 * @throws \Alma\API\RequestError
	 */
	public function share_days() {

		// ini_set('max_execution_time', 30);
		error_log( 'share_days()' );

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

		$last_update_dates = $this->share_of_checkout_helper->get_last_update_date();
		error_log( '$last_update_dates = ' );
		error_log( gettype( $last_update_dates ) );
		error_log( $last_update_dates );
		$last_update_date = $last_update_dates['end_time'];

		$dates_to_share = $this->date_helper->get_dates_in_interval( $last_update_date, $share_of_checkout_enabled_date );
		error_log( serialize( $dates_to_share ) );

		foreach ( $dates_to_share as $date ) {
			try {
				$this->share_of_checkout_helper->set_share_of_checkout_from_date( $date );
				$this->share_of_checkout_helper->share_day();
			} catch ( \Exception $e ) {
				error_log( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
				// throw new RequestError($e->getMessage(), null, null);
			}
		}
	}
}
