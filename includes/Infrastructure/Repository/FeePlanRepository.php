<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Entity\Eligibility;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Mapper\EligibilityMapper;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Application\Provider\FeePlanProvider;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Plugin;

class FeePlanRepository {

	/** @var FeePlanListAdapter */
	private FeePlanListAdapter $feePlanListAdapter;

	/** @var ConfigService */
	private ConfigService $configService;

	/**
	 * FeePlanRepository constructor.
	 *
	 * @param ConfigService $configService
	 */
	public function __construct( ConfigService $configService ) {
		almaLogConsole( 'CONSTRUCT' );
		$this->configService = $configService;
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

		almaLogConsole( 'GET ALL' );

		if ( $forceRefresh || ! isset( $this->feePlanListAdapter ) ) {
			$this->retrieveFeePlans();
		}

		return $this->feePlanListAdapter;
	}

	/**
	 * Get a Fee Plan by its plan key (e.g. 'general_3_0_0').
	 *
	 * @param string $planKey
	 * @param bool   $forceRefresh
	 *
	 * @return FeePlanAdapter|null
	 * @throws FeePlanRepositoryException
	 */
	public function getByPlanKey( string $planKey, bool $forceRefresh = false ): ?FeePlanAdapter {

		almaLogConsole( 'GET BY PLAN KEY' );

		if ( $forceRefresh || ! isset( $this->feePlanListAdapter ) ) {
			$this->retrieveFeePlans();
		}

		/** @var FeePlanAdapter $feePlanAdapter */
		foreach ( $this->feePlanListAdapter as $feePlanAdapter ) {
			if ( $feePlanAdapter->getPlanKey() === $planKey ) {
				return $feePlanAdapter;
			}
		}

		return null;
	}

	public function deleteAll() {
		$this->configService->deleteFeePlansConfiguration();
	}

	/**
	 * Retrieve Fee Plans from Alma and set their local configuration.
	 *
	 * @return void
	 * @throws FeePlanRepositoryException
	 */
	public function retrieveFeePlans(): void {
		try {
			// Get Fee Plans. (From API)
			/** @var FeePlanProvider $feePlanProvider */
			$feePlanProvider    = Plugin::get_container()->get( FeePlanProvider::class );
			$feePlanListAdapter = new FeePlanListAdapter( $feePlanProvider->getFeePlanList() );
			$this->saveKeysToConfig( $feePlanListAdapter );

			// Add local configuration to Fee Plans. (local min and max amount set in the plugin form)
			$feePlanListAdapter = $this->setLocalConfiguration( $feePlanListAdapter );

			// Get Eligibility only on shop
			if ( ! ContextHelper::isAdmin() ) {
				// Add Eligibility to Fee Plans. (Installment Plans from API) /** @var EligibilityProvider $eligibilityProvider */ {
				$eligibilityProvider = Plugin::get_container()->get( EligibilityProvider::class );
				$eligibilityDto      = ( new EligibilityMapper() )
					->buildEligibilityDto(
						ContextHelper::getCart(),
						ContextHelper::getCustomer(),
						$feePlanListAdapter->filterEnabled()
					);
				$installmentPlanList = $eligibilityProvider->getEligibilityList( $eligibilityDto );
				$feePlanListAdapter  = $this->setInstallmentPlanList( $feePlanListAdapter, $installmentPlanList );
			}

			$this->feePlanListAdapter = $feePlanListAdapter;

		} catch ( FeePlanServiceException|EligibilityServiceException $e ) {
			throw new FeePlanRepositoryException( $e->getMessage() );
		}
	}

	/**
	 * Init Fee Plan list options with values from the Alma API.
	 *
	 * @param FeePlanListAdapter $feePlanListAdapter The given Fee Plan list to initialize.
	 *
	 * @return void
	 * @todo move to configRepository?
	 */
	private function saveKeysToConfig( FeePlanListAdapter $feePlanListAdapter ) {

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
	 * We get Fee Plans from Alma with their allowed status and min/max amounts.
	 * Then we add merchant configurations to the fee plans.
	 * This includes enabling/disabling plans and setting the min/max purchase amounts
	 *
	 * @param FeePlanListAdapter $feePlanListAdapter
	 *
	 * @return FeePlanListAdapter
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
				$feePlanAdapter->resetOverrideMaxPurchaseAmount();
				$feePlanAdapter->resetOverrideMinPurchaseAmount();
			}
		}

		return $feePlanListAdapter;
	}

	private function setInstallmentPlanList( FeePlanListAdapter $feePlanListAdapter, EligibilityList $eligibilityList ): FeePlanListAdapter {
		/** @var Eligibility $eligibility */
		foreach ( $eligibilityList as $eligibility ) {
			/** @var FeePlanAdapter $feePlanAdapter */
			foreach ( $feePlanListAdapter as $feePlanAdapter ) {
				if ( $feePlanAdapter->getPlanKey() === $eligibility->getPlanKey() ) {
					$feePlanAdapter->setEligibility( $eligibility->isEligible() );
					$feePlanAdapter->setPaymentPlan( $eligibility->getPaymentPlan() );
					$feePlanAdapter->setCustomerTotalCostAmount( $eligibility->getCustomerTotalCostAmount() );
					$feePlanAdapter->setAnnualInterestRate( $eligibility->getAnnualInterestRate() );
					$feePlanAdapter->setCustomerTotalCostBps( $eligibility->getCustomerTotalCostBps() );
					$feePlanAdapter->setCustomerFee( $eligibility->getCustomerFee() );
				}
			}
		}

		return $feePlanListAdapter;
	}
}
