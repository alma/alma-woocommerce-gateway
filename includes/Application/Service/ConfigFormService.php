<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Infrastructure\ClientConfiguration;
use Alma\Gateway\Application\Entity\FeePlansConfigForm;
use Alma\Gateway\Application\Entity\KeysConfigForm;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;

class ConfigFormService {

	private ConfigService $configService;
	private AuthenticationService $authenticationService;
	private FeePlanRepository $feePlanRepository;

	public function __construct( ConfigService $configService, FeePlanRepository $feePlanRepository, AuthenticationService $authenticationService ) {
		$this->configService         = $configService;
		$this->feePlanRepository     = $feePlanRepository;
		$this->authenticationService = $authenticationService;
	}

	/**
	 * Check if fee plans are valid.
	 *
	 * @param FeePlansConfigForm $configForm
	 *
	 * @return FeePlansConfigForm
	 */
	public function checkFeePlansForm( FeePlansConfigForm $configForm ): FeePlansConfigForm {

		$feePlanList = $configForm->getFeePlans();
		foreach ( $feePlanList as $planKey => &$feePlanArray ) {
			// Min must be lower than max
			if ( $feePlanArray[ FeePlansConfigForm::FEE_PLAN_MIN_AMOUNT_KEY ] >= $feePlanArray[ FeePlansConfigForm::FEE_PLAN_MAX_AMOUNT_KEY ] ) {
				$configForm->addError( "Le montant minimum d'un Fee Plan doit être inférieur au montant maximum." );
				$feePlanArray[ FeePlansConfigForm::FEE_PLAN_ENABLED_KEY ]    = false;
				$feePlanArray[ FeePlansConfigForm::FEE_PLAN_MIN_AMOUNT_KEY ] = $this->feePlanRepository->getByPlanKey( $planKey )->getMinPurchaseAmount();
				$feePlanArray[ FeePlansConfigForm::FEE_PLAN_MAX_AMOUNT_KEY ] = $this->feePlanRepository->getByPlanKey( $planKey )->getMaxPurchaseAmount();
			}

			// Min must be higher than default min
			if ( $feePlanArray[ FeePlansConfigForm::FEE_PLAN_MIN_AMOUNT_KEY ] < $this->feePlanRepository->getByPlanKey( $planKey )->getMinPurchaseAmount() ) {
				$configForm->addError( "Le montant minimum d'un Fee Plan doit être supérieur ou égal a la valeur par défaut." );
				$feePlanArray[ FeePlansConfigForm::FEE_PLAN_ENABLED_KEY ]    = false;
				$feePlanArray[ FeePlansConfigForm::FEE_PLAN_MIN_AMOUNT_KEY ] = $this->feePlanRepository->getByPlanKey( $planKey )->getMinPurchaseAmount();
			}

			// Max must be lower than default max
			if ( $feePlanArray[ FeePlansConfigForm::FEE_PLAN_MAX_AMOUNT_KEY ] > $this->feePlanRepository->getByPlanKey( $planKey )->getMaxPurchaseAmount() ) {
				$configForm->addError( "Le montant maximum d'un Fee Plan doit être inférieur ou égal a la valeur par défaut." );
				$feePlanArray[ FeePlansConfigForm::FEE_PLAN_ENABLED_KEY ]    = false;
				$feePlanArray[ FeePlansConfigForm::FEE_PLAN_MAX_AMOUNT_KEY ] = $this->feePlanRepository->getByPlanKey( $planKey )->getMaxPurchaseAmount();
			}
		}
		$configForm->setFeePlans( $feePlanList );

		return $configForm;
	}

	/**
	 * Check if API keys are valid.
	 *
	 * @param KeysConfigForm $configForm
	 *
	 * @return KeysConfigForm
	 */
	public function checkKeysForm( KeysConfigForm $configForm ): KeysConfigForm {

		// Check test merchant id
		$newTestMerchantId = '';
		if ( $configForm->isTestKeyChanged() ) {
			$newTestMerchantId = $this->getMerchantId( $configForm->getNewTestKey(), ClientConfiguration::TEST_MODE );
			if ( empty( $newTestMerchantId ) ) {
				$configForm->resetNewTestKey();
				$configForm->addError( 'La clé API de test n\'est pas valide.' );
			}
		} elseif ( $configForm->isNewTestKeyEmpty() ) {
			$configForm->resetNewTestKey();
		}

		// Check live merchant id
		$newLiveMerchantId = '';
		if ( $configForm->isLiveKeyChanged() ) {
			$newLiveMerchantId = $this->getMerchantId( $configForm->getNewLiveKey() );
			if ( empty( $newLiveMerchantId ) ) {
				$configForm->resetNewLiveKey();
				$configForm->addError( 'La clé API de production n\'est pas valide.' );
			}
		} elseif ( $configForm->isNewLiveKeyEmpty() ) {
			$configForm->resetNewLiveKey();
		}

		// Save merchant id if valid.
		$configForm->setNewMerchantId( $newTestMerchantId, $newLiveMerchantId );

		return $configForm;
	}

	/**
	 * Get the merchant id associated with the given API key.
	 *
	 * @param string $key The API key.
	 * @param string $mode The mode (test or live).
	 *
	 * @return string The merchant id or empty string if the key is invalid.
	 */
	private function getMerchantId( string $key, string $mode = ClientConfiguration::LIVE_MODE ) {
		if ( ! empty( $key ) ) {
			return $this->authenticationService->checkAuthentication( $key, $mode );
		}

		return '';
	}
}
