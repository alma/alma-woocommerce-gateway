<?php
/**
 * InPageBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks/Inpage
 * @namespace Alma\Woocommerce\Blocks\Inpage
 */

namespace Alma\Woocommerce\Blocks\Inpage;

use Alma\Woocommerce\Blocks\AlmaBlock;
use Alma\Woocommerce\Gateways\Inpage\InPageGateway;
use Alma\Woocommerce\Helpers\ConstantsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * InPageBlock.
 */
class InPageBlock extends AlmaBlock {

	/**
	 * The Gateway.
	 *
	 * @var InPageGateway
	 */
	protected $gateway;


	/**
	 * Paiement Gateway name.
	 *
	 * @var string
	 */
	protected $name = ConstantsHelper::GATEWAY_ID_IN_PAGE;


	/**
	 * Get the gateway id.
	 *
	 * @return string
	 */
	public function get_gateway_id() {
		return ConstantsHelper::GATEWAY_ID_IN_PAGE;
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->gateway = new InPageGateway();
	}


}
