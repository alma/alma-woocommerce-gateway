<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\WooCommerce\Proxy\SettingsProxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsService {

	/**
	 * @var SettingsProxy
	 */
	private $settings_proxy;

	public function __construct( SettingsProxy $settings_proxy ) {
		$this->settings_proxy = $settings_proxy;
	}

	public function init_admin_form() {
		$this->settings_proxy->set_admin_form();
	}
}
