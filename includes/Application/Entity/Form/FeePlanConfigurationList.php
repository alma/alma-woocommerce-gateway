<?php

namespace Alma\Gateway\Application\Entity\Form;

use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use ArrayObject;

class FeePlanConfigurationList extends ArrayObject {

	/** @var array $errors */
	private array $errors = [];

	/**
	 * FeePlanConfigurationList constructor.
	 *
	 * @param array  $array
	 * @param int    $flags
	 * @param string $iteratorClass
	 */
	public function __construct( array $array = [], int $flags = 0, string $iteratorClass = "ArrayIterator" ) {
		parent::__construct(
			array_filter( $array, function ( $item ) {
				return $item instanceof FeePlanConfiguration;
			} ),
			$flags,
			$iteratorClass
		);
	}

	/**
	 * Check if the fee plans are valid.
	 *
	 * @return $this
	 */
	public function validate( FeePlanListAdapter $feePlanListAdapter ): FeePlanConfigurationList {
		/** @var FeePlanAdapter $feePlanAdapter */
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {
			/** @var FeePlanConfiguration $feePlan */
			foreach ( $this as $feePlan ) {
				if ( $feePlanAdapter->getPlanKey() === $feePlan->getPlanKey() ) {
					$feePlan->validate( $feePlanAdapter );
					$this->errors = array_merge( $this->errors, $feePlan->getErrors() );
				}
			}
		}

		return $this;
	}

	/**
	 * Get the errors.
	 *
	 * @return array The errors.
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Reset the fee plan list.
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->exchangeArray( [] );
	}
}
