<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Entity\Eligibility;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Mapper\EligibilityMapper;
use Alma\Gateway\Application\Provider\EligibilityProviderAwareTrait;
use Alma\Gateway\Application\Provider\EligibilityProviderFactory;
use Alma\Gateway\Application\Provider\FeePlanProviderAwareTrait;
use Alma\Gateway\Application\Provider\FeePlanProviderFactory;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;

class FeePlanRepository {

	use FeePlanProviderAwareTrait;
	use EligibilityProviderAwareTrait;

	/** @var array */
	private array $feePlanListAdapter;

	/** @var ConfigService */
	private ConfigService $configService;

	/**
	 * FeePlanRepository constructor.
	 *
	 * @param ConfigService              $configService
	 * @param FeePlanProviderFactory     $feePlanProviderFactory
	 * @param EligibilityProviderFactory $eligibilityProviderFactory
	 */
	public function __construct( ConfigService $configService, FeePlanProviderFactory $feePlanProviderFactory, EligibilityProviderFactory $eligibilityProviderFactory ) {
		$this->configService              = $configService;
		$this->feePlanProviderFactory     = $feePlanProviderFactory;
		$this->eligibilityProviderFactory = $eligibilityProviderFactory;
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

		if ( $forceRefresh || ! isset( $this->feePlanListAdapter['all'][0] ) ) {
			almalog( 'NO CACHE' );
			$this->feePlanListAdapter['all'][0] = $this->retrieveFeePlans();
		}
		almalog( 'CACHE' );

		return $this->feePlanListAdapter['all'][0];
	}

	/**
	 * Get all Fee Plans with their local configuration and Eligibility
	 *
	 * @param int  $cartTotal
	 * @param bool $forceRefresh Whether to force a refresh of the fee plan list.
	 *
	 * @return FeePlanListAdapter
	 * @throws FeePlanRepositoryException
	 */
	public function getAllWithEligibility( int $cartTotal = 0, bool $forceRefresh = false ): FeePlanListAdapter {

		if ( $forceRefresh || ! isset( $this->feePlanListAdapter['all-with-eligibility'][ $cartTotal ] ) ) {
			$this->feePlanListAdapter['all-with-eligibility'][ $cartTotal ] = $this->retrieveFeePlans( $cartTotal );
		}

		return $this->feePlanListAdapter['all-with-eligibility'][ $cartTotal ];
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

		if ( $forceRefresh || ! isset( $this->feePlanListAdapter['all'][0] ) ) {
			$this->feePlanListAdapter['all'][0] = $this->retrieveFeePlans();
		}

		/** @var FeePlanAdapter $feePlanAdapter */
		foreach ( $this->feePlanListAdapter['all'][0] as $feePlanAdapter ) {
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
	protected function retrieveFeePlans( $cartTotal = 0 ): FeePlanListAdapter {
		try {
			// Get Fee Plans. (From API)
			$this->getFeePlanProvider();
			$feePlanListAdapter = new FeePlanListAdapter( $this->feePlanProvider->getFeePlanList() );
			$this->saveKeysToConfig( $feePlanListAdapter );

			// Add local configuration to Fee Plans. (local min and max amount set in the plugin form)
			$feePlanListAdapter = $this->setLocalConfiguration( $feePlanListAdapter );

			// Get Eligibility only on shop
			if ( ! ContextHelper::isAdmin() && $cartTotal > 0 ) {
				// Add Eligibility to Fee Plans. (Installment Plans from API)
				$this->getEligibilityProvider();
				$eligibilityDto      = ( new EligibilityMapper() )
					->buildEligibilityDto(
						ContextHelper::getCart(),
						ContextHelper::getCustomer(),
						$feePlanListAdapter->filterEnabled()
					);
				$installmentPlanList = $this->eligibilityProvider->getEligibilityList( $eligibilityDto );
				$feePlanListAdapter  = $this->setInstallmentPlanList( $feePlanListAdapter, $installmentPlanList );
			}

			return $feePlanListAdapter;

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
