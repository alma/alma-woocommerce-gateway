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
	 * Logger.
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Share of checkout helper.
	 *
	 * @var Alma_WC_Share_Of_Checkout_Helper
	 */
	private $share_of_checkout_helper;

	/**
	 * Date helper.
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

		// @todo debug test to remove later.
		if ( isset( $_GET['test_soc'] ) ) {
			$this->share_days();
			exit;
		}

		if ( ! wp_next_scheduled( self::CRON_ACTION ) ) {
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
		$this->share_days();
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return void
	 */
	public function share_days() {

		error_log( 'share_days()' );

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return;
		}

		if ( 'yes' !== alma_wc_plugin()->settings->share_of_checkout_enabled ) {
			$this->logger->info( 'Share Of Checkout is not enabled' );
			return;
		}

		// @todo debug test to remove later.
		// $share_of_checkout_enabled_date = alma_wc_plugin()->settings->share_of_checkout_enabled_date;
		$share_of_checkout_enabled_date = '2022-06-10';

		$last_update_date = $this->share_of_checkout_helper->get_last_update_date();
		error_log( '$last_update_date = ' . gmdate( 'Y-m-d', $last_update_date ) );

		$dates_to_share = $this->date_helper->get_dates_in_interval( $last_update_date, $share_of_checkout_enabled_date );

		error_log( '$dates_to_share = ' . serialize( $dates_to_share ) );

		foreach ( $dates_to_share as $date ) {
			$this->share_of_checkout_helper->set_share_of_checkout_from_date( $date );
			$this->share_of_checkout_helper->share_day();
		}
	}
}
