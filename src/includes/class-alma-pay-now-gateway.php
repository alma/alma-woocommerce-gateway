<?php
/**
 * Alma_Pay_Now_Gateway.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 *
 * @since 4.3.0
 */

namespace Alma\Woocommerce;

use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;

}

/**
 * Alma_Pay_Now_Gateway
 */
class Alma_Pay_Now_Gateway extends Alma_Gateway_Abstract {

	public function __construct() {
		 $this->id        = Alma_Constants_Helper::GATEWAY_PN_ID;
		$this->has_fields = true;
		// @todo
		$this->method_title       = __( 'Payment in 1', 'alma-gateway-for-woocommerce' );
		$this->method_description = __( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.', 'alma-gateway-for-woocommerce' );

		parent::__construct();
	}

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 */
	public function get_icon() {
		return Alma_Assets_Helper::get_icon( $this->get_title(), $this->id, Alma_Constants_Helper::ALMA_LOGO_PATH );
	}

}
