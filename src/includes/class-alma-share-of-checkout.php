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

/**
 * Alma_Share_Of_Checkout
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
	 * Checkout Helper.
	 *
	 * @var Alma_Share_Of_Checkout_Helper
	 */
	protected $checkout_helper;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger          = new Alma_Logger();
		$this->settings        = new Alma_Settings();
		$this->checkout_helper = new Alma_Share_Of_Checkout_Helper();
	}

	/**
	 * Share of checkout execution.
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
				$this->logger->info( sprintf( 'Soc : test mode enabled or no soc allowed %s %s', $this->settings->is_test(), $this->settings->share_of_checkout_enabled ) );
				return;
			}

			$today = new \DateTime();

			$last_sharing_date = null;

			if ( ! empty( $settings_date ) ) {
				$last_sharing_date = new \DateTime( $settings_date );
				$this->logger->info( sprintf( 'Soc : last sharing date:  %s', $last_sharing_date->format( 'Y-m-d' ) ) );
			} else {
				$this->logger->info( 'Soc : NO last sharing date' );

			}

			if (
				empty( $settings_date )
				|| (
					! empty( $last_sharing_date )
					&& $today->format( 'Y-m-d' ) > $last_sharing_date->format( 'Y-m-d' )
				)
			) {
				$this->logger->info( 'Soc : Send the data to soc init' );

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

		$this->logger->info( sprintf( 'Soc : $share_of_checkout_enabled_date %s', $share_of_checkout_enabled_date ) );

		try {
			$last_update_date = $this->checkout_helper->get_last_update_date();
		} catch ( \Exception $e ) {
			$this->logger->error( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
			$last_update_date = $this->checkout_helper->get_default_last_update_date();
		}

		$this->logger->info( sprintf( 'Soc : $last_update_date %s', $last_update_date ) );

		$from_date = max( $last_update_date, $share_of_checkout_enabled_date );

		$end_date = new \DateTime();
		$end_date->modify( '-1 day' );
		$end_date = $end_date->format( 'Y-m-d' );

		$this->logger->info( sprintf( 'Soc : $from_date %s $to_date %s', $from_date, $end_date ) );

		$my_soc_data = $this->checkout_helper->get_payload( $from_date, $end_date );

		if ( count( $my_soc_data ) > 0 ) {
			$this->logger->info( sprintf( 'Soc : $my_soc_data %s', wp_json_encode( $my_soc_data ) ) );

			$this->settings->send_soc_data( $my_soc_data );
		}
	}
}
