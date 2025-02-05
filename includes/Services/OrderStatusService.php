<?php
/**
 * Order status service.
 *
 * @package Alma\Woocommerce\Services
 */

namespace Alma\Woocommerce\Services;

use Alma\API\DependenciesError;
use Alma\API\Exceptions\ParametersException;
use Alma\API\Exceptions\RequestException;
use Alma\API\ParamsError;
use Alma\API\RequestError;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Exceptions\NoOrderException;
use Alma\Woocommerce\Exceptions\RequirementsException;
use Alma\Woocommerce\WcProxy\OrderProxy;
use WC_Order;

/**
 * Order status service.
 */
class OrderStatusService {


	/**
	 * Alma settings.
	 *
	 * @var AlmaSettings
	 */
	private $alma_settings;

	/**
	 * Alma logger.
	 *
	 * @var AlmaLogger
	 */
	private $alma_logger;

	/**
	 * Order proxy.
	 *
	 * @var OrderProxy
	 */
	private $order_proxy;

	/**
	 * OrderStatusService constructor.
	 *
	 * @param AlmaSettings $alma_settings Alma settings.
	 * @param OrderProxy   $order_proxy Order proxy.
	 * @param AlmaLogger   $alma_logger Alma logger.
	 */
	public function __construct(
		$alma_settings = null,
		$order_proxy = null,
		$alma_logger = null
	) {
		$this->alma_settings = isset( $alma_settings ) ? $alma_settings : new AlmaSettings();
		$this->order_proxy   = isset( $order_proxy ) ? $order_proxy : new OrderProxy();

		$this->alma_logger = isset( $alma_logger ) ? $alma_logger : new AlmaLogger();
	}

	/**
	 * Send order status to Alma.
	 *
	 * @param int    $order_id order id.
	 * @param string $old_status old order status.
	 * @param string $new_status new order status.
	 *
	 * @return void
	 */
	public function send_order_status( $order_id, $old_status, $new_status ) {// phpcs:ignore
		try {
			$order = $this->order_proxy->get_order_by_id( $order_id );
		} catch ( NoOrderException $e ) {
			$this->alma_logger->error( 'Send order status no order : ' . $e->getMessage() );

			return;
		}

		if ( ! $this->is_alma_order( $order ) || ! $this->alma_client_is_init() ) {
			return;
		}

		try {
			$alma_payment_id = $this->order_proxy->get_alma_payment_id( $order );
		} catch ( RequirementsException $e ) {
			$this->alma_logger->warning( 'Send order Status RequirementsException : ' . $e->getMessage() );

			return;
		}

		$this->send_order_status_on_alma_client(
			$alma_payment_id,
			$this->order_proxy->get_display_order_reference( $order ),
			$new_status
		);
	}

	/**
	 * Check if the order is an Alma Order.
	 *
	 * @param WC_Order $order Order ID.
	 *
	 * @return bool
	 */
	private function is_alma_order( $order ) {
		$payment_method = $this->order_proxy->get_order_payment_method( $order );

		return 'alma' === $payment_method || 'alma_in_page' === $payment_method;
	}

	/**
	 * Init Alma client.
	 *
	 * @return bool
	 */
	private function alma_client_is_init() {
		try {
			$this->alma_settings->get_alma_client();
		} catch ( DependenciesError $e ) {
			$this->alma_logger->info( 'Send order Status DependenciesError : ' . $e->getMessage() );

			return false;
		} catch ( ParamsError $e ) {
			$this->alma_logger->info( 'Send order Status ParamsError : ' . $e->getMessage() );

			return false;
		} catch ( AlmaException $e ) {
			$this->alma_logger->info( 'Send order Status AlmaException : ' . $e->getMessage() );

			return false;
		}

		return true;
	}

	/**
	 * Use Alma Client to send order status.
	 *
	 * @param string $alma_payment_id Alma payment ID.
	 * @param string $order_reference WC order reference.
	 * @param string $new_status New status order.
	 *
	 * @return void
	 */
	private function send_order_status_on_alma_client( $alma_payment_id, $order_reference, $new_status ) {
		try {
			$this->alma_settings->alma_client->payments->addOrderStatusByMerchantOrderReference(
				$alma_payment_id,
				$order_reference,
				$new_status,
				$this->is_shipped( $new_status )
			);
		} catch ( ParametersException $e ) {
			$this->alma_logger->error( 'Send order Status ParametersException : ' . $e->getMessage() );
		} catch ( RequestError $e ) {
			$this->alma_logger->error( 'Send order Status RequestError : ' . $e->getMessage() );
		} catch ( RequestException $e ) {
			$this->alma_logger->error( 'Send order Status RequestException : ' . $e->getMessage() );
		}
	}

	/**
	 * Check if the order is shipped.
	 *
	 * @param string $status Order status.
	 *
	 * @return bool | null
	 */
	private function is_shipped( $status ) {
		switch ( $status ) {
			case 'pending':
			case 'on-hold':
			case 'processing':
			case 'failed':
			case 'refunded':
			case 'cancelled':
			case 'checkout-draft':
				return false;
			case 'completed':
				return true;
			default:
				return null;
		}
	}
}
