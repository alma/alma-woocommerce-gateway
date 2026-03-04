<?php

namespace Alma\Gateway\Infrastructure\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\Exception\ParametersException;
use Alma\Client\Domain\Entity\Eligibility;
use Alma\Client\Domain\Entity\EligibilityList;
use Alma\Client\Domain\Entity\FeePlanList;
use Alma\Gateway\Application\Exception\Provider\EligibilityProviderException;
use Alma\Gateway\Application\Mapper\EligibilityMapper;
use Alma\Gateway\Application\Provider\EligibilityProviderAwareTrait;
use Alma\Gateway\Application\Provider\EligibilityProviderFactory;
use Alma\Gateway\Application\Provider\FeePlanProviderAwareTrait;
use Alma\Gateway\Application\Provider\FeePlanProviderFactory;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Service\LoggerServiceAwareTrait;
use Throwable;

class FeePlanRepository {

	use FeePlanProviderAwareTrait;
	use EligibilityProviderAwareTrait;
	use LoggerServiceAwareTrait;

	/** Transient key prefix for caching fee plans from API */
	const TRANSIENT_FEE_PLANS_PREFIX = 'alma_fee_plans_';

	/** Transient key prefix for caching eligibility from API */
	const TRANSIENT_ELIGIBILITY_PREFIX = 'alma_eligibility_';

	/** Cache TTL for fee plans: 1 hour */
	const TRANSIENT_FEE_PLANS_TTL = 3600;

	/** Cache TTL for eligibility: 5 minutes */
	const TRANSIENT_ELIGIBILITY_TTL = 300;

	/**
	 * In-process static cache to avoid multiple API calls within the same PHP process.
	 * @var FeePlanList|null
	 */
	private static ?FeePlanList $staticFeePlanCache = null;

	/** @var array */
	private array $feePlanListAdapter;

	/** @var ConfigService */
	private ConfigService $configService;

	/** @var BusinessEventsService */
	private BusinessEventsService $businessEventsService;

	/**
	 * FeePlanRepository constructor.
	 *
	 * @param ConfigService              $configService
	 * @param BusinessEventsService      $businessEventsService
	 * @param FeePlanProviderFactory     $feePlanProviderFactory
	 * @param EligibilityProviderFactory $eligibilityProviderFactory
	 */
	public function __construct(
		ConfigService $configService,
		BusinessEventsService $businessEventsService,
		FeePlanProviderFactory $feePlanProviderFactory,
		EligibilityProviderFactory $eligibilityProviderFactory
	) {
		$this->configService              = $configService;
		$this->businessEventsService      = $businessEventsService;
		$this->feePlanProviderFactory     = $feePlanProviderFactory;
		$this->eligibilityProviderFactory = $eligibilityProviderFactory;
	}

	/**
	 * Clear all transient and static caches.
	 * Should be called when merchant settings are updated.
	 */
	public static function clearTransientCache(): void {
		self::$staticFeePlanCache = null;

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::TRANSIENT_FEE_PLANS_PREFIX . '%',
				'_transient_timeout_' . self::TRANSIENT_FEE_PLANS_PREFIX . '%',
				'_transient_' . self::TRANSIENT_ELIGIBILITY_PREFIX . '%',
				'_transient_timeout_' . self::TRANSIENT_ELIGIBILITY_PREFIX . '%'
			)
		);
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
			$this->feePlanListAdapter['all'][0] = $this->retrieveFeePlans( 0, $forceRefresh );
		}

		return $this->feePlanListAdapter['all'][0];
	}

	/**
	 * Get all Fee Plans with their local configuration and Eligibility but doesn't filter them.
	 *
	 * @param int  $cartTotal
	 * @param bool $forceRefresh Whether to force a refresh of the fee plan list.
	 *
	 * @return FeePlanListAdapter
	 * @throws FeePlanRepositoryException
	 */
	public function getAllWithEligibility( int $cartTotal = 0, bool $forceRefresh = false ): FeePlanListAdapter {

		if ( $forceRefresh || ! isset( $this->feePlanListAdapter['all-with-eligibility'][ $cartTotal ] ) ) {
			$this->feePlanListAdapter['all-with-eligibility'][ $cartTotal ] = $this->retrieveFeePlans( $cartTotal,
				$forceRefresh );
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
			$this->feePlanListAdapter['all'][0] = $this->retrieveFeePlans( 0, $forceRefresh );
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
		$this->clearTransientCache();
	}

	/**
	 * Retrieve Fee Plans from Alma and set their local configuration.
	 * Uses a two-level cache: static (in-process) + transient (cross-process).
	 *
	 * @param int  $cartTotal
	 * @param bool $forceRefresh
	 *
	 * @return FeePlanListAdapter
	 * @throws FeePlanRepositoryException
	 */
	protected function retrieveFeePlans( int $cartTotal = 0, bool $forceRefresh = false ): FeePlanListAdapter {
		try {
			$feePlanList = $forceRefresh ? null : $this->getFeePlanListFromCache();

			if ( null === $feePlanList ) {
				$this->getFeePlanProvider();
				$feePlanList = $this->feePlanProvider->getFeePlanList();
				$this->storeFeePlanListInCache( $feePlanList );
				$this->getLogger()->debug( 'Fee plans fetched from API and cached.' );
			}

			$feePlanListAdapter = ( new FeePlanListAdapter( $feePlanList ) )->filterAvailable();
			$this->saveKeysToConfig( $feePlanListAdapter );
			$feePlanListAdapter = $this->setLocalConfiguration( $feePlanListAdapter );

			// Get Eligibility only on shop
			if ( ! ContextHelper::isAdmin() && $cartTotal > 0 ) {
				$this->getEligibilityProvider();
				$eligibilityDto = ( new EligibilityMapper() )
					->buildEligibilityDto(
						ContextHelper::getCart(),
						ContextHelper::getCustomer(),
						$feePlanListAdapter->filterEnabled()
					);

				$cacheKey            = $this->getEligibilityCacheKey( $eligibilityDto->toArray() );
				$cachedEligibility   = get_transient( $cacheKey );
				$installmentPlanList = is_string( $cachedEligibility ) ? unserialize( $cachedEligibility ) : null; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

				if ( ! $installmentPlanList instanceof EligibilityList ) {
					$installmentPlanList = $this->eligibilityProvider->getEligibilityList( $eligibilityDto );
					set_transient( $cacheKey, serialize( $installmentPlanList ),
						self::TRANSIENT_ELIGIBILITY_TTL ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
					$this->getLogger()->debug( 'Eligibility fetched from API and cached.' );
				} else {
					$this->getLogger()->debug( 'Eligibility loaded from cache.' );
				}

				$this->businessEventsService->updateEligibility( $installmentPlanList );
				$feePlanListAdapter = $this->setInstallmentPlanList( $feePlanListAdapter, $installmentPlanList );
			}

			return $feePlanListAdapter;

		} catch ( EligibilityProviderException|Throwable $e ) {
			throw new FeePlanRepositoryException( 'Can not retrieve Fee Plans', 0, $e );
		}
	}

	/**
	 * Build the transient key for fee plans cache.
	 * Includes merchant_id and environment to avoid cache collisions
	 * between merchants or between sandbox/live environments.
	 *
	 * @return string
	 */
	protected function getFeePlansCacheKey(): string {
		$merchantId  = $this->configService->getMerchantId() ?? 'unknown';
		$environment = $this->configService->isLive() ? 'live' : 'test';

		return self::TRANSIENT_FEE_PLANS_PREFIX . md5( $merchantId . '_' . $environment );
	}

	/**
	 * Build the transient key for eligibility cache.
	 * Includes merchant_id, environment and a hash of the eligibility DTO.
	 *
	 * @param array $eligibilityDtoArray
	 *
	 * @return string
	 */
	private function getEligibilityCacheKey( array $eligibilityDtoArray ): string {
		$merchantId  = $this->configService->getMerchantId() ?? 'unknown';
		$environment = $this->configService->isLive() ? 'live' : 'test';

		return self::TRANSIENT_ELIGIBILITY_PREFIX . md5( $merchantId . '_' . $environment . '_' . wp_json_encode( $eligibilityDtoArray ) );
	}

	/**
	 * Get FeePlanList from cache (static first, then transient).
	 *
	 * @return FeePlanList|null
	 */
	private function getFeePlanListFromCache(): ?FeePlanList {
		// Level 1: in-process static cache (free, instant)
		if ( self::$staticFeePlanCache instanceof FeePlanList ) {
			$this->getLogger()->debug( 'Fee plans loaded from static (in-process) cache.' );

			return self::$staticFeePlanCache;
		}

		// Level 2: transient cache (cross-process, database)
		$raw = get_transient( $this->getFeePlansCacheKey() );

		if ( ! is_string( $raw ) ) {
			return null;
		}

		$feePlanList = @unserialize( $raw ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		if ( $feePlanList instanceof FeePlanList ) {
			self::$staticFeePlanCache = $feePlanList;
			$this->getLogger()->debug( 'Fee plans loaded from transient cache.' );

			return $feePlanList;
		}

		return null;
	}

	/**
	 * Store FeePlanList in both caches.
	 *
	 * @param FeePlanList $feePlanList
	 */
	private function storeFeePlanListInCache( FeePlanList $feePlanList ): void {
		self::$staticFeePlanCache = $feePlanList;
		set_transient( $this->getFeePlansCacheKey(), serialize( $feePlanList ),
			self::TRANSIENT_FEE_PLANS_TTL ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Init Fee Plan list options with values from the Alma API.
	 *
	 * @param FeePlanListAdapter $feePlanListAdapter The given Fee Plan list to initialize.
	 *
	 * @return void
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
				$this->getLogger()->debug(
					'Invalid min/max purchase amount for fee plan ' . $feePlanAdapter->getPlanKey(),
					[
						'exception' => $e,
						'planKey'   => $feePlanAdapter->getPlanKey(),
					]
				);
				$feePlanAdapter->resetOverrideMaxPurchaseAmount();
				$feePlanAdapter->resetOverrideMinPurchaseAmount();
			}
		}

		return $feePlanListAdapter;
	}

	private function setInstallmentPlanList( FeePlanListAdapter $feePlanListAdapter, EligibilityList $eligibilityList ): FeePlanListAdapter {
		if ( $eligibilityList->count() === 0 ) {
			return new FeePlanListAdapter( [] );
		}

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
