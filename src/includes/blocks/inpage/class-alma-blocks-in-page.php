<?php
/**
 * Alma_Blocks_In_Page.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce\Blocks\Inpage
 */

namespace Alma\Woocommerce\Blocks\Inpage;

use Alma\Woocommerce\Blocks\Alma_Blocks;
use Alma\Woocommerce\Gateways\Inpage\Alma_Payment_Gateway_In_Page;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks_In_Page.
 */
class Alma_Blocks_In_Page extends Alma_Blocks {

	/**
	 * The Gateway.
	 *
	 * @var Alma_Payment_Gateway_In_Page
	 */
	protected $gateway;


	/**
	 * Paiement Gateway name.
	 *
	 * @var string
	 */
	protected $name = Alma_Constants_Helper::GATEWAY_ID_IN_PAGE;

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->gateway = new Alma_Payment_Gateway_In_Page();
	}


}
