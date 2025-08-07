<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\DTO\RefundDto;
use Alma\API\Exception\ParametersException;
use Alma\Gateway\Application\Exception\ContainerException;
use Alma\Gateway\Application\Exception\EligibilityServiceException;
use Alma\Gateway\Application\Exception\MerchantServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\API\EligibilityService;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Application\Service\API\PaymentService;
use Alma\Gateway\Infrastructure\WooCommerce\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WordPressProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Repository\OrderRepository;
use Alma\Gateway\Plugin;

class GatewayService {

	public const CUSTOMER_RETURN = 'alma_customer_return';
	public const IPN_CALLBACK    = 'alma_ipn_callback';

	/** @var HooksProxy */
	private HooksProxy $hooks_proxy;

	/** @var EligibilityService|null The Eligibility Service if available */
	private ?EligibilityService $eligibility_service = null;

	/** @var FeePlanService|null The Fee plan Service if available */
	private ?FeePlanService $fee_plan_service = null;

	public function __construct(
		HooksProxy $hooks_proxy
	) {
		$this->hooks_proxy = $hooks_proxy;
	}

	/**
	 *  Set the eligibility service. This can't be done in the constructor because the service
	 *  could be not available at the time if the plugin in not fully configured.
	 *
	 * @param EligibilityService $eligibility_service
	 *
	 * @return void
	 */
	public function set_eligibility_service( EligibilityService $eligibility_service ): void {
		$this->eligibility_service = $eligibility_service;
	}

	/**
	 * Set the fee plan service. This can't be done in the constructor because the service
	 * could be not available at the time if the plugin in not fully configured.
	 *
	 * @param FeePlanService $fee_plan_service
	 *
	 * @return void
	 */
	public function set_fee_plan_service( FeePlanService $fee_plan_service ): void {
		$this->fee_plan_service = $fee_plan_service;
	}

	/**
	 * Init and Load the gateways
	 *
	 * Load the Frontend gateways if the user is not in the admin area.
	 * Load the Backend gateways if the user is in the admin area.
	 * But also load the Frontend gateways if the user is in the admin area but not on the Gateway settings page.
	 * It's useful to do refunds on, the order page for example.
	 */
	public function load_gateway() {
		// Init Gateway
		if ( WordPressProxy::is_admin() ) {
			if ( WooCommerceProxy::is_gateway_settings_page() ) {
				almalog( 'settings page' );
				$this->hooks_proxy->load_backend_gateway();
			} else {
				almalog( 'another admin page' );
				$this->hooks_proxy->load_frontend_gateways();
			}
			// Add links to gateway.
			$this->hooks_proxy->add_gateway_links(
				Plugin::get_instance()->get_plugin_file(),
				array( $this, 'plugin_action_links' )
			);
		} else {
			$this->hooks_proxy->load_frontend_gateways();
		}

		// Configure the hooks linked to the gateways
		add_action( 'woocommerce_order_status_changed', array( $this, 'woocommerce_order_status_changed' ), 10, 3 );
	}

	/**
	 * Callback function for the event "order status changed".
	 * This method will make full refund if possible.
	 *
	 * @param int    $order_id Order id.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 *
	 * @throws ParametersException
	 */
	public function woocommerce_order_status_changed( int $order_id, string $old_status, string $new_status ): void {

		/** @var OrderRepository $order_repository */
		$order_repository = Plugin::get_container()->get( OrderRepository::class );
		$order            = $order_repository->findById( $order_id );

		if ( 'refunded' === $new_status || 'cancelled' === $new_status ) {
			if ( $order->isRefundable() ) {

				/** @var PaymentService $payment_service */
				$payment_service = Plugin::get_container()->get( PaymentService::class );

				$payment_service->refund_payment(
					$order->getPaymentId(),
					( new RefundDto() )
						->setAmount( DisplayHelper::price_to_cent( $order->getRemainingRefundAmount() ) )
						->setMerchantReference( $order->get_order_number() )
						->setComment( L10nHelper::__( 'Full refund requested by the merchant' ) )
				);

				$order->add_order_note(
					sprintf(
						L10nHelper::__( 'Order fully refunded by %s.' ),
						wp_get_current_user()->display_name
					)
				);
			}
		}
	}


	/**
	 * Configure each gateway with eligibility and fee plans
	 *
	 * @throws EligibilityServiceException
	 * @throws MerchantServiceException|ContainerException
	 */
	public function configure_gateway() {

		if ( ! $this->eligibility_service || ! $this->fee_plan_service ) {
			$logger = Plugin::get_container()->get( LoggerService::class );
			$logger->debug( 'configure_gateway : Errors in services eligibility or fee plan' );

			return;
		}

		/** @var AbstractGateway $gateway */
		foreach ( WooCommerceProxy::get_alma_gateways() as $gateway ) {
			if ( WooCommerceProxy::is_checkout_page() ) {
				$gateway->configure_eligibility( $this->eligibility_service->get_eligibility_list() );
				$gateway->configure_fee_plans( $this->fee_plan_service->get_fee_plan_list() );
			}
		}
	}


	/**
	 * Configure returns (ipn and customer_return ) if configuration is done
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public function configure_returns() {
		add_action(
			'woocommerce_api_' . self::IPN_CALLBACK,
			array( Plugin::get_container()->get( IpnService::class ), 'handle_ipn_callback' )
		);
		add_action(
			'woocommerce_api_' . self::CUSTOMER_RETURN,
			array( Plugin::get_container()->get( IpnService::class ), 'handle_customer_return' )
		);
	}

	/**
	 * Add links to gateway.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ): array {
		$setting_link = WordPressProxy::admin_url( 'admin.php?page=wc-settings&tab=checkout&section=alma_config_gateway' );
		$plugin_links = array(
			sprintf( '<a href="%s">%s</a>', $setting_link, L10nHelper::__( 'Settings' ) ),
		);

		return array_merge( $plugin_links, $links );
	}
}
