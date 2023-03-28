<?php
/**
 * Alma_Share_Of_Checkout.
 *
 * @since 4.1.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


use Alma\Woocommerce\Admin\Helpers\Alma_Share_Of_Checkout_Helper;
use Alma\Woocommerce\Admin\Helpers\Alma_Date_Helper;

/**
 * Alma_Share_Of_Checkout_Helper
 */
class Alma_Share_Of_Checkout {

	/**
	 * Logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;


	/**
	 * Db Settings.
	 *
	 * @var Alma_Settings
	 */
	protected $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger   = new Alma_Logger();
		$this->settings = new Alma_Settings();
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

			$today = new \DateTime();

			$last_sharing_date = null;

			if ( ! empty( $settings_date ) ) {
				$last_sharing_date = new \DateTime( $settings_date );
			}

			if (
				empty( $settings_date )
				|| $today->format( 'Y-m-d' ) > $last_sharing_date->format( 'Y-m-d' )
			) {
				$this->settings->__set( 'share_of_checkout_last_sharing_date', $today->format( 'Y-m-d' ) );
				$this->settings->save();

				$this->share_days();
			}
		} catch ( \Exception $e ) {
			$this->settings->__set( 'share_of_checkout_last_sharing_date', $settings_date );
			$this->settings->save();

			$this->logger->error(
				sprintf( 'An error occurred when sending soc data. Message "%s"', $e->getMessage() ),
				$e->getTrace()
			);
		}
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return void
	 * @throws Exceptions\Alma_Api_Share_Of_Checkout_Exception Alma_Api_Share_Of_Checkout_Exception.
	 * @throws \Exception General Exception.
	 */
	public function share_days() {
		$share_of_checkout_enabled_date = $this->settings->share_of_checkout_enabled_date;

		try {
			$last_update_date = Alma_Share_Of_Checkout_Helper::get_last_update_date();
		} catch ( \Exception $e ) {
			$this->logger->error( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
			$last_update_date = Alma_Share_Of_Checkout_Helper::get_default_last_update_date();
		}

		$from_date = max( $last_update_date, $share_of_checkout_enabled_date );

		$dates_to_share = Alma_Date_Helper::get_dates_in_interval( $from_date );

		foreach ( $dates_to_share as $date ) {
			$this->settings->send_soc_data( Alma_Share_Of_Checkout_Helper::get_payload( $date ) );
		}
	}
}
