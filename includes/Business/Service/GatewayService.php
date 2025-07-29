<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\EligibilityServiceException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Service\API\EligibilityService;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

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
	 */
	public function load_gateway() {

		$logger = Plugin::get_container()->get( LoggerService::class );
		$logger->debug( WordPressProxy::is_admin() );

		// Init Gateway
		if ( WordPressProxy::is_admin() ) {
			$logger->debug( 'backend gateway loading' );
			$this->hooks_proxy->load_backend_gateway();

			// Add links to gateway.
			$this->hooks_proxy->add_gateway_links(
				Plugin::get_instance()->get_plugin_file(),
				array( $this, 'plugin_action_links' )
			);
		} else {
			$logger->debug( 'frontend gateway loading' );
			$this->hooks_proxy->load_frontend_gateways();
		}
	}

	/**
	 * Configure each gateway with eligibility and fee plans
	 * @throws EligibilityServiceException
	 * @throws MerchantServiceException|ContainerException
	 */
	public function configure_gateway() {

		if ( ! $this->eligibility_service || ! $this->fee_plan_service ) {
			return;
		}

		if ( WooCommerceProxy::is_checkout_page() ) {
			/** @var AbstractGateway $gateway */
			foreach ( WooCommerceProxy::get_alma_gateways() as $gateway ) {
				$gateway->configure_eligibility( $this->eligibility_service->get_eligibility_list() );
				$gateway->configure_fee_plans( $this->fee_plan_service->get_fee_plan_list() );
				$gateway->configure_ipn();
			}
		}
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
