<?php
/**
 * Alma_Blocks_Standard.
 *
 * @since
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce\Blocks\Standard;

use Alma\Woocommerce\Blocks\Alma_Blocks;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Standard;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Alma_Blocks_Standard
 */
class Alma_Blocks_Standard extends Alma_Blocks {
	/**
	 * @var Alma_Payment_Gateway_Standard
	 */
	protected $gateway;



	/**
	 * Paiement Gateway name
	 *
	 * @var string
	 */
	protected $name = Alma_Constants_Helper::GATEWAY_ID;

	public function initialize() {
		parent::initialize();

		$this->gateway = new Alma_Payment_Gateway_Standard();
	}
}
