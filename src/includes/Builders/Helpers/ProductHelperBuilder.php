<?php
/**
 * ProductHelperBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Helpers
 * @namespace Alma\Woocommerce\Builders\Helpers
 */

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\ProductHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class ProductHelperBuilder.
 */
class ProductHelperBuilder {

	use BuilderTrait;

	/**
	 * Product Helper.
	 *
	 * @return ProductHelper
	 */
	public function get_instance() {
		return new ProductHelper(
			$this->get_alma_logger(),
			$this->get_alma_settings(),
			$this->get_cart_factory(),
			$this->get_core_factory()
		);
	}
}
