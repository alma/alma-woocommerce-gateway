<?php
/**
 * PayNowBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks/Standard
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Woocommerce\Blocks\Standard;

use Alma\Woocommerce\Blocks\AlmaBlock;
use Alma\Woocommerce\Gateways\Standard\PayNowGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks_Pay_Now.
 */
class PayNowBlock extends AlmaBlock {

	/**
	 * The Gateway.
	 *
	 * @var PayNowGateway
	 */
	protected $gateway;

	/**
	 * Paiement Gateway name
	 *
	 * @var string
	 */
	protected $name = ConstantsHelper::GATEWAY_ID_PAY_NOW;

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID_PAY_NOW;
	}


	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->gateway = new PayNowGateway();
	}
}
