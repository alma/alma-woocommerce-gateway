<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Adapter\FeePlanListAdapterInterface;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Helper class to display the cart and product widgets with a shortcode.
 * This class manages the display for Classic themes.
 */
class ShortcodeWidgetHelper {

	/** @var string class used by merchant's shortcode to display widget */
	const WIDGET_CLASS = 'alma-widget';

	/** @var string Default class to display widget */
	const WIDGET_DEFAULT_CLASS = 'alma-default-widget';

	/** @var string Shortcode tag for the cart widget */
	const CART_SHORTCODE_TAG = 'alma-cart-eligibility';

	/** @var string Shortcode tag for the product widget */
	const PRODUCT_SHORTCODE_TAG = 'alma-product-eligibility';

	/**
	 * Display the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @return void
	 */
	public function displayDefaultCartWidget() {
		add_action(
			'woocommerce_proceed_to_checkout',
			function () {
				echo do_shortcode( sprintf( '[%s class="%s"]', self::CART_SHORTCODE_TAG, self::WIDGET_DEFAULT_CLASS ) );
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param string                      $environment The API mode (live or test).
	 * @param string                      $merchantId The merchant ID.
	 * @param int                         $price The total price of the cart in cents.
	 * @param FeePlanListAdapterInterface $feePlanListAdapter The list of fee plans.
	 * @param string                      $language The language code (e.g., 'en', 'fr', etc.).
	 * @param bool                        $displayWidget Whether to display the widget or not.
	 *
	 * @return void
	 */
	public function initCartShortcode( string $environment, string $merchantId, int $price, FeePlanListAdapterInterface $feePlanListAdapter, string $language, bool $displayWidget = false ) {
		if ( $displayWidget ) {
			$this->addScriptsAndStyles();
			$this->addParameters( $environment, $merchantId, $price, $feePlanListAdapter, $language );
			$this->addShortcode( self::CART_SHORTCODE_TAG );
		} else {
			$this->addEmptyShortcode( self::CART_SHORTCODE_TAG );
		}
	}

	/**
	 * Display the Alma product widget shortcode.
	 * This shortcode can be used to display the Alma product widget
	 *
	 * @return void
	 */
	public function displayDefaultProductWidget() {
		add_action(
			'woocommerce_before_add_to_cart_form',
			function () {
				echo do_shortcode(
					sprintf(
						'[%s class="%s"]',
						self::PRODUCT_SHORTCODE_TAG,
						self::WIDGET_DEFAULT_CLASS
					)
				);
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param string                      $environment The API mode (live or test).
	 * @param string                      $merchantId The merchant ID.
	 * @param int                         $price The total price of the cart in cents.
	 * @param FeePlanListAdapterInterface $feePlanListAdapter The list of fee plans.
	 * @param string                      $language The language code.
	 * @param bool                        $displayWidget Whether to display the widget or not.
	 *
	 * @return void
	 */
	public function initProductShortcode( string $environment, string $merchantId, int $price, FeePlanListAdapterInterface $feePlanListAdapter, string $language, bool $displayWidget = false ) {
		if ( $displayWidget ) {
			$this->addScriptsAndStyles();
			$this->addParameters( $environment, $merchantId, $price, $feePlanListAdapter, $language );
			$this->addShortcode( self::PRODUCT_SHORTCODE_TAG );
		} else {
			$this->addEmptyShortcode( self::PRODUCT_SHORTCODE_TAG );
		}
	}

	/**
	 * Enqueue the scripts and styles needed for the Alma widget.
	 *
	 * @return void
	 */
	private function addScriptsAndStyles() {
		AssetsHelper::enqueueWidgetStyle();
		AssetsHelper::enqueueWidgetScript( '1.0.0' );
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
	private function addParameters( string $environment, string $merchantId, int $price, FeePlanListAdapterInterface $feePlanListAdapter, string $language ) {
		wp_localize_script(
			'alma-frontend-widget-implementation',
			'alma_widget_settings',
			array(
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
			)
		);
	}

	/**
	 * Add the shortcode for the Alma widget.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return void
	 */
	private function addShortcode( string $tag ) {
		add_shortcode(
			$tag,
			function ( $atts ) use ( $tag ) {

				$class = isset( $atts['class'] ) ? htmlspecialchars( $atts['class'] ) : self::WIDGET_CLASS;
				$style = '';

				return sprintf(
					'<div class="%s %s" style="%s"></div>',
					$tag,
					$class,
					$style
				);
			}
		);
	}

	/**
	 * Add an empty shortcode that returns a div with the given tag.
	 * That's used when the widget is not enabled, to avoid breaking the layout.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return void
	 */
	private function addEmptyShortcode( string $tag ) {
		add_shortcode(
			$tag,
			function () use ( $tag ) {
				return sprintf(
					'<div class="%s"></div>',
					$tag,
				);
			}
		);
	}
}
