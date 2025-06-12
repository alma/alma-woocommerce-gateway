<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\L10nHelper;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PayLaterGateway extends AbstractFrontendGateway {

	public const GATEWAY_TYPE = 'pay_later';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay later with Alma';
		$this->method_title = L10nHelper::__( 'Payment deferred with Alma' );

		parent::__construct();
	}
}
