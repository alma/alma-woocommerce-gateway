<?php

namespace Alma\Gateway\Business\Service;

class AdminService {

	/**
	 * @var SettingsService
	 */
	private $settings_service;

	/**
	 * AdminService constructor.
	 *
	 * @param SettingsService $settings_service
	 */
	public function __construct( SettingsService $settings_service ) {
		$this->settings_service = $settings_service;
		$this->settings_service->init_admin_form();
	}
}
