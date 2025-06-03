<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Service\API\EligibilityService;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Model\Gateway;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

class GatewayService {

	/** @var Gateway */
	private Gateway $gateway;

	/** @var HooksProxy */
	private HooksProxy $hooks_proxy;

	public function __construct( Gateway $gateway, HooksProxy $hooks_proxy ) {
		$this->hooks_proxy = $hooks_proxy;
		$this->gateway     = $gateway;
	}

	/**
	 * Init and Load the gateway
	 */
	public function load_gateway() {

		// Load eligibilities
		$eligibility_service = Plugin::get_container()->get( EligibilityService::class );
		$eligibility_service->is_eligible();

		// Init Gateway
		add_action( 'wp_loaded', array( $this->hooks_proxy, 'load_gateway' ) );

		// Add links to gateway.
		$this->hooks_proxy->add_gateway_links(
			Plugin::get_instance()->get_plugin_file(),
			array( $this, 'plugin_action_links' )
		);

		WooCommerceProxy::setEligibilities( $eligibility_service->is_eligible() );
	}

	/**
	 * Add links to gateway.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ): array {
		$setting_link = WordPressProxy::admin_url( 'admin.php?page=wc-settings&tab=checkout&section=alma' );
		$plugin_links = array(
			sprintf( '<a href="%s">%s</a>', $setting_link, L10nHelper::__( 'Settings' ) ),
		);

		return array_merge( $plugin_links, $links );
	}
}
