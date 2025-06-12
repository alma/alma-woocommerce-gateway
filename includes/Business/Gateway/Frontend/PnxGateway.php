<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\L10nHelper;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PnxGateway extends AbstractFrontendGateway {
	public const GATEWAY_TYPE = 'pnx';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay in installments with Alma';
		$this->method_title = L10nHelper::__( 'Payment in installments with Alma - 2x 3x 4x' );

		parent::__construct();
	}
}
