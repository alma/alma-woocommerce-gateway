<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Model\Gateway;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

class GatewayService {

	/**
	 * @var Gateway
	 */
	private $gateway;

	/**
	 * @var HooksProxy
	 */
	private $hooks_proxy;

	public function __construct( HooksProxy $hooks_proxy ) {
		$this->hooks_proxy = $hooks_proxy;
	}

	/**
	 * Init and Load the gateway
	 */
	public function load_gateway() {
		// Init Gateway
		$this->gateway = Plugin::get_container()->get( Gateway::class );
		$this->hooks_proxy->load_gateway( Gateway::class );
		// Add links to gateway.
		$this->hooks_proxy->add_gateway_links(
			Plugin::get_instance()->get_plugin_file(),
			array( $this, 'plugin_action_links' )
		);
	}

	/**
	 * Add links to gateway.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$setting_link = WordPressProxy::admin_url( 'admin.php?page=wc-settings&tab=checkout&section=alma' );
		$plugin_links = array(
			printf( '<a href="%s">%s</a>', $setting_link, L10nHelper::__( 'Alma Settings' ) ),
		);

		return array_merge( $plugin_links, $links );
	}
}
