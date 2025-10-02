<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Exception\Service\GatewayConfigurationFormValidatorServiceException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;

class GatewayConfigurationFormValidatorService {

	/** @var FeePlanRepository $feePlanRepository */
	private FeePlanRepository $feePlanRepository;

	/**
	 * ConfigFormValidatorService constructor.
	 *
	 * @param FeePlanRepository $feePlanRepository
	 */
	public function __construct( FeePlanRepository $feePlanRepository ) {
		$this->feePlanRepository = $feePlanRepository;
	}

	/**
	 * Validate the GatewayConfiguration entity.
	 * If the API keys have changed, we need to reset the fee plans.
	 * if the api response is not ok, throw an exception.
	 *
	 * @param GatewayConfigurationForm $gatewayConfiguration
	 *
	 * @return GatewayConfigurationForm
	 * @throws GatewayConfigurationFormValidatorServiceException
	 */
	public function validate( GatewayConfigurationForm $gatewayConfiguration ): GatewayConfigurationForm {

		$keyConfigForm            = $gatewayConfiguration->getKeyConfiguration()->validate();
		$feePlanConfigurationList = $gatewayConfiguration->getFeePlanConfigurationList();

		if ( $keyConfigForm->isMerchantIdChanged() ) {
			// If the API keys have changed, we need to clean the fee plans and reload them from the API
			$this->resetFeePlans( $feePlanConfigurationList );
		}

		// We only validate fee plans if there are any
		if ( count( $feePlanConfigurationList ) ) {
			try {
				$feePlanConfigurationList->validate( $this->feePlanRepository->getAll() );
			} catch ( FeePlanRepositoryException $e ) {
				throw new GatewayConfigurationFormValidatorServiceException( 'Les fee plans n\'ont pas pu être récupérés. Veuillez réessayer plus tard.' );
			}
		}

		return $gatewayConfiguration;
	}


	/**
	 * Reset fee plans in the database and in the settings.
	 * This is used when the API keys have changed to avoid keeping old fee plans.
	 *
	 * @param FeePlanConfigurationList $feePlanConfigurationList
	 *
	 * @return void
	 */
	private function resetFeePlans( FeePlanConfigurationList $feePlanConfigurationList ): void {
		// Delete all fee plans from the database
		$this->feePlanRepository->deleteAll();
		$feePlanConfigurationList->reset();
	}
}
