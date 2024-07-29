<?php
/**
 * GatewayHelperBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Helpers
 *  @namespace Alma\Woocommerce\Builders\Helpers
 */

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\GatewayHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class GatewayHelperBuilder.
 */
class GatewayHelperBuilder {

	use BuilderTrait;

	/**
	 * Tools Helper.
	 *
	 * @return GatewayHelper
	 */
	public function get_instance() {
		return new GatewayHelper(
			$this->get_alma_settings(),
			$this->get_payment_helper(),
			$this->get_checkout_helper(),
			$this->get_cart_factory(),
			$this->get_product_helper(),
			$this->get_core_factory(),
			$this->get_cart_helper(),
			$this->get_php_helper()
		);
	}
}
