<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Provider\FeePlanProvider;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;

class FeePlanRepository {

	/** @var FeePlanProvider */
	private FeePlanProvider $feePlanProvider;

	/** @var FeePlanListAdapter */
	private FeePlanListAdapter $feePlanListAdapter;

	/** @var ConfigService */
	private ConfigService $configService;

	/**
	 * FeePlanRepository constructor.
	 *
	 * @param FeePlanProvider $feePlanProvider
	 * @param ConfigService   $configService
	 *
	 * @throws FeePlanRepositoryException
	 */
	public function __construct( FeePlanProvider $feePlanProvider, ConfigService $configService ) {
		$this->feePlanProvider = $feePlanProvider;
		$this->configService   = $configService;
		// Initialize Fee Plans from Alma API.
		$this->retrieveFeePlans();
	}

	/**
	 * Init Fee Plan list options with values from the Alma API.
	 *
	 * @param FeePlanListAdapter $feePlanListAdapter The given Fee Plan list to initialize.
	 *
	 * @return void
	 */
	public function addAll( FeePlanListAdapter $feePlanListAdapter ) {

		/** @var FeePlanAdapter $feePlanAdapter */
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {

			$defaultPlanList = array(
				'_enabled'    => false,
				'_max_amount' => $feePlanAdapter->getMaxPurchaseAmount(),
				'_min_amount' => $feePlanAdapter->getMinPurchaseAmount(),
			);

			foreach ( $defaultPlanList as $planKey => $defaultValue ) {
				$optionKey = $feePlanAdapter->getPlanKey() . $planKey;
				if ( ! $this->configService->hasSetting( $optionKey ) ) {
					$this->configService->createSetting( $optionKey, $defaultValue );
				}
			}
		}
	}

	/**
	 * Get all Fee Plans with their local configuration.
	 *
	 * @param bool $forceRefresh Whether to force a refresh of the fee plan list.
	 *
	 * @return FeePlanListAdapter
	 * @throws FeePlanRepositoryException
	 */
	public function getAll( bool $forceRefresh = false ): FeePlanListAdapter {
		if ( $forceRefresh || ! isset( $this->feePlanListAdapter ) ) {
			$this->retrieveFeePlans();
		}

		return $this->feePlanListAdapter;
	}

	/**
	 * Get a Fee Plan by its plan key (e.g. 'general_3_0_0').
	 *
	 * @param string $planKey
	 *
	 * @return FeePlanAdapter|null
	 */
	public function getByPlanKey( string $planKey ): ?FeePlanAdapter {
		/** @var FeePlanAdapter $feePlanAdapter */
		foreach ( $this->feePlanListAdapter as $feePlanAdapter ) {
			if ( $feePlanAdapter->getPlanKey() === $planKey ) {
				return $feePlanAdapter;
			}
		}

		return null;
	}

	/**
	 * Retrieve Fee Plans from Alma and set their local configuration.
	 *
	 * @return void
	 * @throws FeePlanRepositoryException
	 */
	private function retrieveFeePlans(): void {
		try {
			$feePlanListAdapter = new FeePlanListAdapter( $this->feePlanProvider->getFeePlanList() );
			$this->addAll( $feePlanListAdapter );
			$this->feePlanListAdapter = $this->setLocalConfiguration( $feePlanListAdapter );
		} catch ( FeePlanServiceException $e ) {
			throw new FeePlanRepositoryException( $e->getMessage() );
		}
	}

	/**
	 * We get Fee Plans from Alma with their allowed status and min/max amounts.
	 * Then we add merchant configurations to the fee plans.
	 * This includes enabling/disabling plans and setting the min/max purchase amounts
	 *
	 * @param FeePlanListAdapter $feePlanListAdapter
	 *
	 * @return FeePlanListAdapter
	 * @throws FeePlanRepositoryException
	 */
	private function setLocalConfiguration( FeePlanListAdapter $feePlanListAdapter ): FeePlanListAdapter {
		/** @var FeePlanAdapter $feePlanAdapter */
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {
			if ( $this->configService->isFeePlanEnabled( $feePlanAdapter->getPlanKey() ) ) {
				$feePlanAdapter->enable();
			}

			try {
				$feePlanAdapter->setOverrideMinPurchaseAmount( $this->configService->getMinPurchaseAmount( $feePlanAdapter->getPlanKey() ) );
				$feePlanAdapter->setOverrideMaxPurchaseAmount( $this->configService->getMaxPurchaseAmount( $feePlanAdapter->getPlanKey() ) );
			} catch ( ParametersException $e ) {
				throw new FeePlanRepositoryException( $e->getMessage() );
			}
		}

		return $feePlanListAdapter;
	}
}
