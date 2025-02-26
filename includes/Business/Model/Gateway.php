<?php

namespace Alma\Gateway\Business\Model;

use Alma\Gateway\WooCommerce\Proxy\HooksProxy;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class Gateway {

	/**
	 * @var HooksProxy
	 */
	private $hooks_proxy;

	/**
	 * Gateway constructor.
	 *
	 * @param HooksProxy $hooks_proxy
	 */
	public function __construct( HooksProxy $hooks_proxy ) {
		$this->hooks_proxy = $hooks_proxy;
	}
}
