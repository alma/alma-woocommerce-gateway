<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\L10nHelper;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PayNowGateway extends AbstractFrontendGateway {

	public const GATEWAY_TYPE = 'pay_now';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay now with Alma';
		$this->method_title = L10nHelper::__( 'Payment with Alma' );

		parent::__construct();
	}
}
