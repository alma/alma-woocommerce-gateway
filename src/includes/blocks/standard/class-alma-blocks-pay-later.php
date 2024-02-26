<?php
/**
 * Alma_Blocks_Pay_Later.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce\Blocks\Standard;
 */

namespace Alma\Woocommerce\Blocks\Standard;

use Alma\Woocommerce\Blocks\Alma_Blocks;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Pay_Later;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks_Pay_Later
 */
class Alma_Blocks_Pay_Later extends Alma_Blocks {

	/**
	 * The Gateway.
	 *
	 * @var Alma_Payment_Gateway_Pay_Later
	 */
	protected $gateway;

	/**
	 * Paiement Gateway name.
	 *
	 * @var string
	 */
	protected $name = Alma_Constants_Helper::GATEWAY_ID_PAY_LATER;

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->gateway = new Alma_Payment_Gateway_Pay_Later();
	}
}
