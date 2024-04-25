<?php
/**
 * StandardBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Block/Standard
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Woocommerce\Blocks\Standard;

use Alma\Woocommerce\Blocks\AlmaBlock;
use Alma\Woocommerce\Gateways\Standard\StandardGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * StandardBlock
 */
class StandardBlock extends AlmaBlock {

	/**
	 * The gateway.
	 *
	 * @var StandardGateway
	 */
	protected $gateway;

	/**
	 * Paiement Gateway name.
	 *
	 * @var string
	 */
	protected $name = ConstantsHelper::GATEWAY_ID;

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID;
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->gateway = new StandardGateway();
	}
}
