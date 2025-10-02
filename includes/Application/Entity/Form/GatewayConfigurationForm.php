<?php

namespace Alma\Gateway\Application\Entity\Form;

class GatewayConfigurationForm {

	/** @var string Field name for Live API key */
	public const FIELD_LIVE_API_KEY = 'live_api_key';

	/** @var string Field name for Test API key */
	public const FIELD_TEST_API_KEY = 'test_api_key';

	/** @var string Field name for Merchant id */
	public const FIELD_MERCHANT_ID = 'merchant_id';

	/** @var string Suffix for Fee Plans min amount fields */
	public const MIN_AMOUNT_SUFFIX = 'min_amount';

	/** @var string Suffix for Fee Plans max amount fields */
	public const MAX_AMOUNT_SUFFIX = 'max_amount';

	/** @var string Suffix for Fee Plans enabled fields */
	public const ENABLED_SUFFIX = 'enabled';

	/** @var string Prefix for Fee Plans fields */
	public const ENABLED_PREFIX = 'general';

	/** @var KeyConfiguration $keyConfiguration */
	private KeyConfiguration $keyConfiguration;

	/** @var FeePlanConfigurationList $feePlanConfigurationList */
	private FeePlanConfigurationList $feePlanConfigurationList;

	/** @var array $additionalSettings additional settings */
	private array $additionalSettings;

	/**
	 * @param KeyConfiguration         $keyConfiguration
	 * @param FeePlanConfigurationList $feePlanConfigurationList
	 * @param array                    $additionalSettings
	 */
	public function __construct(
		KeyConfiguration $keyConfiguration,
		FeePlanConfigurationList $feePlanConfigurationList,
		array $additionalSettings = []
	) {
		$this->keyConfiguration         = $keyConfiguration;
		$this->feePlanConfigurationList = $feePlanConfigurationList;
		$this->additionalSettings       = $additionalSettings;
	}

	/**
	 * Get the value of keyConfiguration
	 *
	 * @return KeyConfiguration
	 */
	public function getKeyConfiguration(): KeyConfiguration {
		return $this->keyConfiguration;
	}

	/**
	 * Get the value of feePlanConfigurationList
	 *
	 * @return FeePlanConfigurationList
	 */
	public function getFeePlanConfigurationList(): FeePlanConfigurationList {
		return $this->feePlanConfigurationList;
	}

	/**
	 * Get the additional settings
	 *
	 * @return array
	 */
	public function getAdditionalSettings(): array {
		return $this->additionalSettings;
	}

	/**
	 * Return errors from both key configuration and fee plan configuration list
	 *
	 * @return array The errors.
	 */
	public function getErrors(): array {
		return array_merge(
			$this->keyConfiguration->getErrors(),
			$this->feePlanConfigurationList->getErrors()
		);
	}
}
