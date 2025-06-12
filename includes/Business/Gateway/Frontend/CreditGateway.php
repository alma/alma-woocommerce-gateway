<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\L10nHelper;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class CreditGateway extends AbstractFrontendGateway {

	public const GATEWAY_TYPE = 'credit';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Credit with Alma';
		$this->method_title = L10nHelper::__( 'Payment in installments with Alma - 10x 12x' );

		parent::__construct();
	}
}
