<?php

namespace Alma\Gateway\Infrastructure\Mapper;

use Alma\Gateway\Application\Entity\Form\FeePlanConfiguration;
use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Entity\Form\KeyConfiguration;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Service\AuthenticationService;
use Alma\Gateway\Application\Service\ConfigService;

class ConfigFormMapper {

	/** @var ConfigService */
	private ConfigService $config_service;
	/** @var array $settings */
	private array $settings;
	/** @var AuthenticationService */
	private AuthenticationService $authentication_service;

	public function __construct( ConfigService $config_service, AuthenticationService $authentication_service ) {
		$this->config_service         = $config_service;
		$this->authentication_service = $authentication_service;
	}

	/**
	 * Map the settings from the CMS form to the GatewayConfiguration entity.
	 *
	 * @param array $settings The settings from the CMS form.
	 *
	 * @return GatewayConfigurationForm The mapped GatewayConfiguration entity.
	 */
	public function from_cms_form( array $settings ): GatewayConfigurationForm {

		// This field is shown only for information and should not be saved
		unset( $settings['merchant_id'] );
		$this->settings = $settings;

		$key_configuration = $this->process_key_configuration();

		if ( $this->config_service->isConfigured() ) {
			$fee_plan_configuration_list = $this->process_fee_plan_configuration_list();
		} else {
			$fee_plan_configuration_list = new FeePlanConfigurationList( array() );
		}

		return new GatewayConfigurationForm( $key_configuration, $fee_plan_configuration_list, $this->settings );
	}

	/**
	 * Map the GatewayConfiguration entity to the settings for the CMS form.
	 *
	 * @param GatewayConfigurationForm $config
	 *
	 * @return array
	 */
	public function to_cms_form( GatewayConfigurationForm $config ): array {

		// Start with additional settings to not lose them
		$settings = $config->getAdditionalSettings();

		// Keys
		$settings[ GatewayConfigurationForm::FIELD_TEST_API_KEY ] = $config->getKeyConfiguration()->getNewTestKey();
		$settings[ GatewayConfigurationForm::FIELD_LIVE_API_KEY ] = $config->getKeyConfiguration()->getNewLiveKey();
		$settings[ GatewayConfigurationForm::FIELD_MERCHANT_ID ]  = $config->getKeyConfiguration()->getNewMerchantId();
		$settings[ GatewayConfigurationForm::FIELD_ENVIRONMENT ]  = $config->getKeyConfiguration()->getNewEnvironment();

		// Fee Plans
		/** @var FeePlanConfiguration $fee_plan_configuration */
		foreach ( $config->getFeePlanConfigurationList() as $fee_plan_configuration ) {
			$settings[ $fee_plan_configuration->getPlanKey() . '_' . GatewayConfigurationForm::MIN_AMOUNT_SUFFIX ] = $fee_plan_configuration->getMinAmount();
			$settings[ $fee_plan_configuration->getPlanKey() . '_' . GatewayConfigurationForm::MAX_AMOUNT_SUFFIX ] = $fee_plan_configuration->getMaxAmount();
			$settings[ $fee_plan_configuration->getPlanKey() . '_' . GatewayConfigurationForm::ENABLED_SUFFIX ]    = $fee_plan_configuration->isEnabled();
		}

		return $settings;
	}

	/**
	 * Process the key configuration from the settings.
	 *
	 * @return KeyConfiguration
	 */
	private function process_key_configuration(): KeyConfiguration {
		$key_configuration = new KeyConfiguration(
			$this->config_service,
			$this->authentication_service,
			$this->settings[ GatewayConfigurationForm::FIELD_TEST_API_KEY ],
			$this->settings[ GatewayConfigurationForm::FIELD_LIVE_API_KEY ],
			$this->settings[ GatewayConfigurationForm::FIELD_ENVIRONMENT ]
		);
		// Remove keys from additional settings to not save them twice
		unset( $this->settings[ GatewayConfigurationForm::FIELD_TEST_API_KEY ] );
		unset( $this->settings[ GatewayConfigurationForm::FIELD_LIVE_API_KEY ] );
		unset( $this->settings[ GatewayConfigurationForm::FIELD_ENVIRONMENT ] );

		return $key_configuration;
	}

	/**
	 * Process the fee plan configuration list from the settings.
	 *
	 * @return FeePlanConfigurationList
	 */
	private function process_fee_plan_configuration_list(): FeePlanConfigurationList {

		$fee_plan_configuration_array = array();
		// Otherwise, we check the form
		foreach ( $this->extract_fee_plan_from_form() as $key => $value ) {
			$feePlanConfiguration           = new FeePlanConfiguration(
				$key,
				$value[ GatewayConfigurationForm::MIN_AMOUNT_SUFFIX ],
				$value[ GatewayConfigurationForm::MAX_AMOUNT_SUFFIX ],
				$value[ GatewayConfigurationForm::ENABLED_SUFFIX ]
			);
			$fee_plan_configuration_array[] = $feePlanConfiguration;
		}

		return new FeePlanConfigurationList( $fee_plan_configuration_array );
	}

	/**
	 * Extract fee plan configurations from the form settings
	 * by grouping trios of min_amount and max_amount and enabled fields.
	 *
	 * @return array
	 */
	private function extract_fee_plan_from_form(): array {

		$groups = array();

		foreach ( $this->settings as $key => $value ) {
			$pattern = '/^(.*)_(' . GatewayConfigurationForm::MIN_AMOUNT_SUFFIX . '|' . GatewayConfigurationForm::MAX_AMOUNT_SUFFIX . ')$/';
			if ( preg_match( $pattern, $key, $matches ) ) {
				$groups[ $matches[1] ][ $matches[2] ] = DisplayHelper::price_to_cent( (float) $value );
				// Remove keys from additional settings to not save them twice
				unset( $this->settings[ $key ] );
			}
			$pattern = '/^(' . GatewayConfigurationForm::ENABLED_PREFIX . '.*)_(' . GatewayConfigurationForm::ENABLED_SUFFIX . ')$/';
			if ( preg_match( $pattern, $key, $matches ) ) {
				$groups[ $matches[1] ][ $matches[2] ] = $value;
				// Remove keys from additional settings to not save them twice
				unset( $this->settings[ $key ] );
			}
		}

		return $groups;
	}
}
