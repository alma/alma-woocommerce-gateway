<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Exception\ContainerException;
use Alma\API\Domain\Helper\WidgetHelperInterface;
use Alma\API\Entity\FeePlanList;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Helper class to display the cart and product widgets.
 * This class manages the display both for Classic and Block themes.
 */
class WidgetHelper implements WidgetHelperInterface {

	/** @var ShortcodeWidgetHelper */
	private ShortcodeWidgetHelper $shortcodeWidgetHelper;

	public function __construct( ShortcodeWidgetHelper $shortcodeWidgetHelper ) {
		$this->shortcodeWidgetHelper = $shortcodeWidgetHelper;
	}

	/**
	 * Display the cart widget with the given price.
	 *
	 * @param string      $environment The API environment (live or test).
	 * @param string      $merchantId The merchant ID.
	 * @param int         $price The total price of the cart in cents.
	 * @param FeePlanList $feePlanList The list of fee plans.
	 * @param string      $language The language code (e.g., 'en', 'fr', etc.).
	 * @param bool        $display_widget Whether to display the widget or not.
	 *
	 * @throws ContainerException
	 */
	public function displayCartWidget( string $environment, string $merchantId, int $price, FeePlanList $feePlanList, string $language, bool $display_widget = false ) {
		$this->shortcodeWidgetHelper->initCartShortcode(
			$environment,
			$merchantId,
			$price,
			$feePlanList,
			$language,
			$display_widget
		);
		$this->shortcodeWidgetHelper->displayDefaultCartWidget();
	}

	/**
	 * Display the product widget with the given price.
	 *
	 * @param string      $environment The API environment (live or test).
	 * @param string      $merchantId The merchant ID.
	 * @param int         $price The price of the product in cents.
	 * @param FeePlanList $feePlanList The list of fee plans.
	 * @param string      $language The language code (e.g., 'en', 'fr', etc.).
	 * @param bool        $display_widget Whether to display the widget or not.
	 *
	 * @throws ContainerException
	 */
	public function displayProductWidget( string $environment, string $merchantId, int $price, FeePlanList $feePlanList, string $language, bool $display_widget = false ) {
		$this->shortcodeWidgetHelper->initProductShortcode(
			$environment,
			$merchantId,
			$price,
			$feePlanList,
			$language,
			$display_widget
		);
		$this->shortcodeWidgetHelper->displayDefaultProductWidget();
	}
}
