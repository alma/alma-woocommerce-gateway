<?php
/**
 * PluginFactory.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Factories
 * @namespace Alma\Woocommerce\Factories
 */

namespace Alma\Woocommerce\Factories;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class PluginFactory.
 */
class PluginFactory {

	/**
	 * Get the admin notifier.
	 *
	 * @return \Alma\Woocommerce\Admin\Services\NoticesService
	 */
	public function get_plugin_admin_notice() {
		return alma_plugin()->admin_notices;
	}

	/***
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
	 * @param string $slug Unique slug.
	 * @param string $class Css class.
	 * @param string $message The message.
	 * @param bool   $dismissible Is this dismissible.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
		$this->get_plugin_admin_notice()->add_admin_notice( $slug, $class, $message, $dismissible );
	}
}
