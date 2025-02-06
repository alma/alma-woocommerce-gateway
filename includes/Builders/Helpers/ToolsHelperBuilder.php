<?php
/**
 * ToolsHelperBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Helpers
 *  @namespace Alma\Woocommerce\Builders\Helpers
 */

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class ToolsHelperBuilder.
 */
class ToolsHelperBuilder {

	use BuilderTrait;

	/**
	 * Tools Helper.
	 *
	 * @return ToolsHelper
	 */
	public function get_instance() {
		return new ToolsHelper(
			$this->get_alma_logger(),
			$this->get_price_factory(),
			$this->get_currency_factory()
		);
	}
}
