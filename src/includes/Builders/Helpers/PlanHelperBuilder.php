<?php
/**
 * PlanHelperBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Helpers
 *  @namespace Alma\Woocommerce\Builders\Helpers
 */

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\PlanHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class PlanHelperBuilder.
 */
class PlanHelperBuilder {

	use BuilderTrait;

	/**
	 * Tools Helper.
	 *
	 * @return PlanHelper
	 */
	public function get_instance() {
		return new PlanHelper(
			$this->get_alma_settings(),
			$this->get_gateway_helper(),
			$this->get_template_loader_helper(),
			$this->get_price_factory()
		);
	}
}
