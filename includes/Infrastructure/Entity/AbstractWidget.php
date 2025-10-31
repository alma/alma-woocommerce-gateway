<?php

namespace Alma\Gateway\Infrastructure\Entity;

use Alma\API\Domain\Adapter\FeePlanListAdapterInterface;
use Alma\API\Domain\Entity\WidgetInterface;
use Alma\API\Domain\ValueObject\Environment;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Service\AssetsService;

abstract class AbstractWidget implements WidgetInterface {

	/** @var string class used by merchant's shortcode to display widget */
	const WIDGET_CLASS = 'alma-widget';

	/** @var string Default class to display widget */
	const WIDGET_DEFAULT_CLASS = 'alma-default-widget';

	protected string $environment;
	protected string $merchantId;
	protected int $price;
	protected bool $displayWidget;
	protected string $language;
	protected AssetsService $assetsService;
	protected FeePlanListAdapter $feePlanListAdapter;
	protected ConfigService $configService;

	public function __construct( ConfigService $configService, AssetsService $assetsService ) {
		$this->configService = $configService;
		$this->assetsService = $assetsService;
	}

	public function configure( FeePlanListAdapterInterface $feePlanListAdapter, Environment $environment, string $merchantId, int $price, bool $displayWidget, string $language ): WidgetInterface {
		$this->feePlanListAdapter = $feePlanListAdapter;
		$this->environment        = $environment;
		$this->merchantId         = $merchantId;
		$this->price              = $price;
		$this->displayWidget      = $displayWidget;
		$this->language           = $language;

		return $this;
	}

	/**
	 * Add the parameters needed for the Alma widget.
	 *
	 * @param string                      $environment The API environment (live or test).
	 * @param string                      $merchantId The merchant ID.
	 * @param int                         $price The price of the product or cart in cents.
	 * @param FeePlanListAdapterInterface $feePlanListAdapter The list of fee plans.
	 * @param string                      $language The language code.
	 *
	 * @return void
	 * @see assets/js/frontend/alma-frontend-widget-implementation.js
	 */
	protected function addParameters( string $environment, string $merchantId, int $price, FeePlanListAdapterInterface $feePlanListAdapter, string $language ): array {
		return array(
			'environment'             => $environment,
			'widget_selector'         => sprintf( '.%s', self::WIDGET_CLASS ),
			'widget_default_selector' => sprintf( '.%s', self::WIDGET_DEFAULT_CLASS ),
			'merchant_id'             => $merchantId,
			'price'                   => $price,
			'language'                => $language,
			'fee_plan_list'           => array_map(
				function ( FeePlanAdapter $plan ) {
					return array(
						'installmentsCount' => $plan->getInstallmentsCount(),
						'minAmount'         => $plan->getOverrideMinPurchaseAmount(),
						'maxAmount'         => $plan->getOverrideMaxPurchaseAmount(),
						'deferredDays'      => $plan->getDeferredDays(),
						'deferredMonths'    => $plan->getDeferredMonths(),
					);
				},
				$feePlanListAdapter->getArrayCopy()
			),
			'hide_if_not_eligible'    => false,
			'transition_delay'        => 5500,
			'monochrome'              => true,
			'hide_border'             => false,
		);
	}
}
