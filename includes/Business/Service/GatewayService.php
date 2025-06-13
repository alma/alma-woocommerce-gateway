<?php

namespace Alma\Gateway\Business\Service;

use Alma\API\Exceptions\EligibilityServiceException;
use Alma\API\Exceptions\MerchantServiceException;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Service\API\EligibilityService;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

class GatewayService {

	/** @var HooksProxy */
	private HooksProxy $hooks_proxy;
	private EligibilityService $eligibility_service;
	private FeePlanService $fee_plan_service;

	public function __construct(
		HooksProxy $hooks_proxy
	) {
		$this->hooks_proxy = $hooks_proxy;
	}

	public function set_eligibility_service( EligibilityService $eligibility_service ): void {
		$this->eligibility_service = $eligibility_service;
	}

	public function set_fee_plan_service( FeePlanService $fee_plan_service ): void {
		$this->fee_plan_service = $fee_plan_service;
	}

	/**
	 * Init and Load the gateways
	 */
	public function load_gateway() {

		// Init Gateway
		if ( WordPressProxy::is_admin() ) {
			$this->hooks_proxy->load_backend_gateway();

			// Add links to gateway.
			$this->hooks_proxy->add_gateway_links(
				Plugin::get_instance()->get_plugin_file(),
				array( $this, 'plugin_action_links' )
			);
		} else {
			$this->hooks_proxy->load_frontend_gateways();
		}
	}

	/**
	 * Configure each gateway with eligibility and fee plans
	 * @throws EligibilityServiceException
	 * @throws MerchantServiceException
	 */
	public function configure_gateway() {

		if ( ! $this->eligibility_service || ! $this->fee_plan_service ) {
			L10nHelper::__( 'Eligibility or Fee Plan service is not set.' );

			return;
		}

		// Init Services
		$this->eligibility_service->retrieve_eligibility();
		$this->fee_plan_service->retrieve_fee_plan_list();

		/** @var AbstractGateway $gateway */
		foreach ( WooCommerceProxy::get_alma_gateways() as $gateway ) {
			$gateway->configure_eligibility( $this->eligibility_service->get_eligibility_list() );
			$gateway->configure_fee_plans( $this->fee_plan_service->get_fee_plan_list() );
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
