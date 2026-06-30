<?php

namespace Alma\Gateway\Application\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\Endpoint\MerchantEndpoint;
use Alma\Client\Application\Exception\Endpoint\MerchantEndpointException;
use Alma\Client\Domain\Entity\FeePlan;
use Alma\Client\Domain\Entity\FeePlanList;
use Alma\Client\Domain\ValueObject\PaymentMethod;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Plugin\Application\Port\FeePlanProviderInterface;
use Alma\Plugin\Infrastructure\Adapter\FeePlanListInterface;
use Psr\Log\LoggerInterface;

class FeePlanProvider implements FeePlanProviderInterface, ProviderInterface {

	/** @var MerchantEndpoint */
	private MerchantEndpoint $merchantEndpoint;

	/** @var FeePlanList */
	private FeePlanList $feePlanList;

	/** @var LoggerInterface */
	private LoggerInterface $loggerService;

	/**
	 * FeePlanProvider constructor.
	 *
	 * @param MerchantEndpoint   $merchantEndpoint
	 * @param LoggerService $loggerService
	 */
	public function __construct( MerchantEndpoint $merchantEndpoint, LoggerService $loggerService ) {
		$this->merchantEndpoint = $merchantEndpoint;
		$this->loggerService    = $loggerService;
	}

	/**
	 * Get the fee plan list.
	 *
	 * @param bool $forceRefresh Whether to force a refresh of the fee plan list.
	 *
	 * @return FeePlanList
	 */
	public function getFeePlanList( bool $forceRefresh = false ): FeePlanList {
		if ( ! isset( $this->feePlanList ) || $forceRefresh ) {
			$this->feePlanList = $this->retrieveFeePlanList();
		}

		return $this->feePlanList;
	}

	/**
	 * Retrieve the fee plan list from the merchant endpoint.
	 */
	private function retrieveFeePlanList(): FeePlanListInterface {

		try {
			return $this->merchantEndpoint->getFeePlanList( FeePlan::KIND_GENERAL, 'all', true )
			                              ->filterFeePlanList( array(
				                              PaymentMethod::CREDIT,
				                              PaymentMethod::PAY_LATER,
				                              PaymentMethod::PAY_NOW,
				                              PaymentMethod::PNX
			                              ) );

		} catch ( MerchantEndpointException $e ) {
			$this->loggerService->error( 'Failed to retrieve fee plan list from merchant endpoint', [
				'exception' => $e,
			] );

			return new FeePlanList();
		}
	}
}
