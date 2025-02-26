<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Model\Gateway;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;

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
		$this->gateway = Plugin::get_container()->get( Gateway::class );
		$this->hooks_proxy->load_gateway( $this->gateway );
	}
}
