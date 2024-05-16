<?php
/**
 * SettingsHelperBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Helpers
 * * @namespace Alma\Woocommerce\Builders\Helpers
 */

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\SettingsHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class SettingsHelperBuilder.
 */
class SettingsHelperBuilder {

	use BuilderTrait;

	/**
	 * Settings Helper.
	 *
	 * @return SettingsHelper
	 */
	public function get_instance() {
		return new SettingsHelper(
			$this->get_internalionalization_helper(),
			$this->get_version_factory(),
			$this->get_tools_helper(),
			$this->get_assets_helper(),
			$this->get_plugin_factory()
		);
	}
}
