<?php

namespace Alma\Gateway\Infrastructure\Entity;

use Alma\API\Domain\Adapter\FeePlanListAdapterInterface;
use Alma\API\Domain\Entity\WidgetInterface;
use Alma\API\Domain\ValueObject\Environment;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;

abstract class AbstractWidget implements WidgetInterface {

	/** @var string class used by merchant's shortcode to display widget */
	const WIDGET_CLASS = 'alma-widget';

	/** @var string Default class to display widget */
	const WIDGET_DEFAULT_CLASS = 'alma-default-widget';

	protected Environment $environment;
	protected string $merchantId;
	protected int $price;
	protected bool $displayWidget;
	protected string $language;
	protected FeePlanListAdapter $feePlanListAdapter;
	protected bool $hasExcludedCategories;

	public function configure(
		FeePlanListAdapterInterface $feePlanListAdapter,
		Environment $environment,
		string $merchantId,
		int $price,
		bool $displayWidget,
		string $language,
		bool $hasExcludedCategories
	): WidgetInterface {
		$this->feePlanListAdapter    = $feePlanListAdapter;
		$this->environment           = $environment;
		$this->merchantId            = $merchantId;
		$this->price                 = $price;
		$this->displayWidget         = $displayWidget;
		$this->language              = $language;
		$this->hasExcludedCategories = $hasExcludedCategories;

		return $this;
	}

	/**
	 * Create the configuration array for the Alma widget JS implementation.
	 *
	 * @return void
	 * @see assets/js/frontend/alma-frontend-widget-implementation.js
	 */
	public function getConfiguration(): array {

		return array(
			'environment'             => $this->environment,
			'widget_selector'         => sprintf( '.%s', self::WIDGET_CLASS ),
			'widget_default_selector' => sprintf( '.%s', self::WIDGET_DEFAULT_CLASS ),
			'merchant_id'             => $this->merchantId,
			'price'                   => $this->price,
			'language'                => $this->language,
			'fee_plan_list'           => array_map(
				function ( FeePlanAdapter $plan ) {
					return array(
						'installmentsCount' => $plan->getInstallmentsCount(),
						'deferredDays'      => $plan->getDeferredDays(),
						'deferredMonths'    => $plan->getDeferredMonths(),
						'minAmount'         => $plan->getOverrideMinPurchaseAmount(),
						'maxAmount'         => $plan->getOverrideMaxPurchaseAmount(),
					);
				},
				$this->feePlanListAdapter->getArrayCopy()
			),
			'hide_if_not_eligible'    => false,
			'transition_delay'        => 5500,
			'monochrome'              => true,
			'hide_border'             => false,
		);
	}
}
