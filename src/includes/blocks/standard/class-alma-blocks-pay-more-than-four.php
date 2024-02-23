<?php
/**
 * Alma_Blocks_Pay_More_Than_Four.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Woocommerce\Blocks\Standard;

use Alma\Woocommerce\Blocks\Alma_Blocks;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Pay_More_Than_Four;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks_Pay_More_Than_Four
 */
class Alma_Blocks_Pay_More_Than_Four extends Alma_Blocks {

	/**
	 * The gateway
	 *
	 * @var Alma_Payment_Gateway_Pay_More_Than_Four
	 */
	protected $gateway;

	/**
	 * Paiement Gateway name.
	 *
	 * @var string
	 */
	protected $name = Alma_Constants_Helper::GATEWAY_ID_MORE_THAN_FOUR;

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->gateway = new Alma_Payment_Gateway_Pay_More_Than_Four();
	}


}
