<?php
/**
 * PayMoreThanFourBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks/Standard
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Woocommerce\Blocks\Standard;

use Alma\Woocommerce\Blocks\AlmaBlock;
use Alma\Woocommerce\Gateways\Standard\PayMoreThanFourGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * PayMoreThanFourBlock
 */
class PayMoreThanFourBlock extends AlmaBlock {

	/**
	 * The gateway
	 *
	 * @var PayMoreThanFourGateway
	 */
	protected $gateway;

	/**
	 * Paiement Gateway name.
	 *
	 * @var string
	 */
	protected $name = ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR;

	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR;
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->gateway = new PayMoreThanFourGateway();
	}


}
