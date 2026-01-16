<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\FeePlanAdapterInterface;
use Alma\API\Domain\Adapter\FeePlanListAdapterInterface;
use Alma\API\Domain\Adapter\FeePlanListInterface;
use Alma\API\Domain\Entity\FeePlanList;
use Alma\API\Domain\ValueObject\PaymentMethod;
use ArrayObject;
use OutOfBoundsException;

/**
 * Adapter for Alma's FeePlanList
 *
 * @see FeePlanList
 */
class FeePlanListAdapter extends ArrayObject implements FeePlanListAdapterInterface {

	/** @var FeePlanListInterface */
	private FeePlanListInterface $almaFeePlanList;

	/**
	 * @param Mixed  $almaFeePlanList Can be a FeePlanListInterface or an array of FeePlanAdapterInterface
	 * @param int    $flags
	 * @param string $iteratorClass
	 */
	public function __construct( $almaFeePlanList, int $flags = 0, string $iteratorClass = "ArrayIterator" ) {

		$almaFeePlanAdapterList = [];
		if ( $almaFeePlanList instanceof FeePlanListInterface ) {
			$this->almaFeePlanList = $almaFeePlanList;

			foreach ( $almaFeePlanList as $feePlan ) {
				// Wrap each FeePlan in a FeePlanAdapter
				$almaFeePlanAdapterList[] = new FeePlanAdapter( $feePlan );
			}
		} elseif ( is_array( $almaFeePlanList ) ) {
			foreach ( $almaFeePlanList as $almaFeePlanAdapter ) {
				if ( $almaFeePlanAdapter instanceof FeePlanAdapterInterface ) {
					$almaFeePlanAdapterList[] = $almaFeePlanAdapter;
				}
			}
		}

		parent::__construct( $almaFeePlanAdapterList, $flags, $iteratorClass );
	}

	/**
	 * Add a FeePlan to the FeePlanList.
	 *
	 * @param FeePlanAdapterInterface $feePlanAdapter
	 *
	 * @return void
	 */
	public function add( FeePlanAdapterInterface $feePlanAdapter ): void {
		$this[] = $feePlanAdapter;
	}

	/**
	 * Add a list of FeePlans to the FeePlanList.
	 *
	 * @param FeePlanListAdapterInterface $feePlanListAdapter
	 *
	 * @return void
	 */
	public function addList( FeePlanListAdapterInterface $feePlanListAdapter ): void {
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {
			$this->add( $feePlanAdapter );
		}
	}

	/**
	 * Returns a FeePlan by its plan key.
	 *
	 * @param string $planKey
	 *
	 * @return FeePlanAdapterInterface
	 *
	 * @throws OutOfBoundsException if the plan key does not exist in the list.
	 */
	public function getByPlanKey( string $planKey ): FeePlanAdapterInterface {
		$filter = array_values( array_filter( $this->getArrayCopy(), function ( $feePlanAdapter ) use ( $planKey ) {
			return $feePlanAdapter->getPlanKey() === $planKey;
		} ) );

		return $filter[0];
	}

	/**
	 * Returns a list of Fee Plans that are only available for the given payment method.
	 *
	 * @param array $paymentMethod
	 *
	 * @return FeePlanListAdapterInterface
	 */
	public function filterFeePlanList( array $paymentMethod ): FeePlanListAdapterInterface {
		$feePlanListAdapter = new FeePlanListAdapter( [] );
		if ( in_array( PaymentMethod::CREDIT, $paymentMethod ) ) {
			$feePlanListAdapter->addList( new FeePlanListAdapter( array_values( array_filter( $this->getArrayCopy(),
				function ( FeePlanAdapter $feePlanAdapter ) {
					return $feePlanAdapter->isCredit();
				} ) ) ) );
		}
		if ( in_array( PaymentMethod::PNX, $paymentMethod ) ) {
			$feePlanListAdapter->addList( new FeePlanListAdapter( array_values( array_filter( $this->getArrayCopy(),
				function ( FeePlanAdapter $feePlanAdapter ) {
					return $feePlanAdapter->isPnXOnly();
				} ) ) ) );
		}
		if ( in_array( PaymentMethod::PAY_LATER, $paymentMethod ) ) {
			$feePlanListAdapter->addList( new FeePlanListAdapter( array_values( array_filter( $this->getArrayCopy(),
				function ( FeePlanAdapter $feePlanAdapter ) {
					return $feePlanAdapter->isPayLaterOnly();
				} ) ) ) );
		}
		if ( in_array( PaymentMethod::PAY_NOW, $paymentMethod ) ) {
			$feePlanListAdapter->addList( new FeePlanListAdapter( array_values( array_filter( $this->getArrayCopy(),
				function ( FeePlanAdapter $feePlanAdapter ) {
					return $feePlanAdapter->isPayNow();
				} ) ) ) );
		}

		return $feePlanListAdapter;
	}

	/**
	 * Returns a list of Fee Plans that are enabled.
	 *
	 * @return FeePlanListAdapterInterface
	 */
	public function filterEnabled(): FeePlanListAdapterInterface {
		$feePlanListAdapter = new FeePlanListAdapter( [] );
		$feePlanListAdapter->addList( new FeePlanListAdapter( array_values( array_filter( $this->getArrayCopy(),
			function ( FeePlanAdapter $feePlanAdapter ) {
				return $feePlanAdapter->isEnabled();
			} ) ) ) );

		return $feePlanListAdapter;
	}

	/**
	 * Returns a list of Fee Plans that are eligible.
	 *
	 * @return FeePlanListAdapterInterface
	 */
	public function filterEligible(): FeePlanListAdapterInterface {
		$feePlanListAdapter = new FeePlanListAdapter( [] );
		$feePlanListAdapter->addList( new FeePlanListAdapter( array_values( array_filter( $this->getArrayCopy(),
			function ( FeePlanAdapter $feePlanAdapter ) {
				return $feePlanAdapter->isEligible();
			} ) ) ) );

		return $feePlanListAdapter;
	}

	/**
	 * Orders the Fee Plans based on the given payment method order,
	 * then by installments count (ascending), then by deferred days (ascending).
	 *
	 * @param array $orderedPaymentMethodList
	 *
	 * @return FeePlanListAdapterInterface
	 */
	public function orderBy( array $orderedPaymentMethodList ): FeePlanListAdapterInterface {
		$feePlanListAdapter = $this->getArrayCopy();

		usort( $feePlanListAdapter,
			function ( FeePlanAdapter $a, FeePlanAdapter $b ) use ( $orderedPaymentMethodList ) {

				// Priority 1: Get gateway priorities based on $orderedPaymentMethodList order
				$priorityA = array_search( $a->getPaymentMethod(), $orderedPaymentMethodList, true );
				$priorityB = array_search( $b->getPaymentMethod(), $orderedPaymentMethodList, true );

				// If gateway not found in list, put it at the end
				if ( $priorityA === false ) {
					$priorityA = PHP_INT_MAX;
				}
				if ( $priorityB === false ) {
					$priorityB = PHP_INT_MAX;
				}

				// If gateway priority is different, sort by priority
				if ( $priorityA !== $priorityB ) {
					return $priorityA <=> $priorityB;
				}

				// Priority 2: Same gateway, sort by installments count (1 to 12)
				$installmentsA = $a->getInstallmentsCount();
				$installmentsB = $b->getInstallmentsCount();

				if ( $installmentsA !== $installmentsB ) {
					return $installmentsA <=> $installmentsB;
				}

				// Priority 3: Same installments count, sort by deferred days (15, 30, etc.)
				$deferredDaysA = $a->getDeferredDays();
				$deferredDaysB = $b->getDeferredDays();

				return $deferredDaysA <=> $deferredDaysB;
			} );

		return new FeePlanListAdapter( $feePlanListAdapter );
	}
}
