<?php
/**
 * CartHelperBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Helpers
 * @namespace Alma\Woocommerce\Builders\Helpers
 */

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CartHelperBuilder.
 */
class CartHelperBuilder {

	use BuilderTrait;

	/**
	 * Cart Helper.
	 *
	 * @return CartHelper
	 */
	public function get_instance() {
		return new CartHelper(
			$this->get_tools_helper(),
			$this->get_session_factory(),
			$this->get_version_factory(),
			$this->get_cart_factory(),
			$this->get_alma_settings(),
			$this->get_alma_logger(),
			$this->get_customer_helper()
		);
	}
}
