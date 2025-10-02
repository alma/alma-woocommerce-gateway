<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\FeePlanAdapterInterface;
use Alma\API\Domain\Adapter\FeePlanListAdapterInterface;
use Alma\API\Domain\Entity\FeePlanList;
use ArrayObject;
use BadMethodCallException;

/**
 * Adapter for Alma's FeePlanList
 *
 * @see FeePlanList
 */
class FeePlanListAdapter extends ArrayObject implements FeePlanListAdapterInterface {

	/** @var FeePlanList */
	private FeePlanList $almaFeePlanList;

	public function __construct( $almaFeePlanList = [], $flags = 0, $iteratorClass = "ArrayIterator" ) {

		$this->almaFeePlanList  = $almaFeePlanList;
		$almaFeePlanAdapterList = [];
		foreach ( $almaFeePlanList as $feePlan ) {
			// Wrap each FeePlan in a FeePlanAdapter
			$almaFeePlanAdapterList[] = new FeePlanAdapter( $feePlan );
		}

		parent::__construct( $almaFeePlanAdapterList, $flags, $iteratorClass );
	}

	/**
	 * Dynamic call to all FeePlanList methods
	 */
	public function __call( string $name, array $arguments ) {

		if ( method_exists( $this->almaFeePlanList, $name ) ) {
			return $this->almaFeePlanList->{$name}( ...$arguments );
		}

		throw new BadMethodCallException( "Method $name (→ $name) does not exists on FeePlanList" );
	}

	/**
	 * Returns a list of Fee Plans that are only available for the given payment method.
	 *
	 * @param array $paymentMethod
	 *
	 * @return FeePlanListAdapterInterface
	 */
	public function filterFeePlanList( array $paymentMethod ): FeePlanListAdapterInterface {
		return new FeePlanListAdapter( $this->almaFeePlanList->filterFeePlanList( $paymentMethod ) );
	}

	/**
	 * Returns a list of Fee Plans that are enabled.
	 *
	 * @return FeePlanListAdapterInterface
	 */
	public function filterEnabled(): FeePlanListAdapterInterface {
		return new FeePlanListAdapter( $this->almaFeePlanList->filterEnabled() );
	}

	/**
	 * Returns a FeePlan by its plan key.
	 *
	 * @param string $planKey
	 *
	 * @return FeePlanAdapterInterface
	 *
	 * @throws \OutOfBoundsException if the plan key does not exist in the list.
	 */
	public function getByPlanKey( string $planKey ): FeePlanAdapterInterface {
		$feePlan = $this->almaFeePlanList->getByPlanKey( $planKey );

		return new FeePlanAdapter( $feePlan );
	}
}
