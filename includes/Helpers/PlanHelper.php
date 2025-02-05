<?php
/**
 * PlanHelper.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce\Helpers;

use Alma\Woocommerce\Exceptions;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Factories\PriceFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PlanHelper
 */
class PlanHelper {



	/**
	 * The settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;


	/**
	 * The gateway helper.
	 *
	 * @var GatewayHelper
	 */
	protected $gateway_helper;

	/**
	 * The template loader.
	 *
	 * @var TemplateLoaderHelper
	 */
	protected $template_loader;


	/**
	 * The price factory.
	 *
	 * @var PriceFactory
	 */
	protected $price_factory;

	/**
	 * Constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param AlmaSettings         $alma_settings The alma settings.
	 * @param GatewayHelper        $gateway_helper  The gateway helper.
	 * @param TemplateLoaderHelper $template_loader The template loader.
	 * @param PriceFactory         $price_factory The price factory.
	 */
	public function __construct( $alma_settings, $gateway_helper, $template_loader, $price_factory ) {
		$this->alma_settings   = $alma_settings;
		$this->gateway_helper  = $gateway_helper;
		$this->template_loader = $template_loader;
		$this->price_factory   = $price_factory;

	}

	/**
	 * Render the checkout fields.
	 *
	 * @param array  $eligibilities The eligitibilies.
	 * @param array  $eligible_plans The eligibles plans.
	 * @param string $gateway_id The gateway id.
	 * @param string $default_plan The default plan.
	 * @return void
	 *
	 * @throws Exceptions\AlmaException Exception.
	 */
	public function render_checkout_fields( $eligibilities, $eligible_plans, $gateway_id, $default_plan = null ) {

		if ( empty( $eligible_plans[ $gateway_id ] ) ) {
			$this->template_loader->get_template( 'alma-checkout-no-plans.php' );
			return;
		}

		if ( empty( $eligible_plans ) ) {
			return;
		}

		$this->template_loader->get_template(
			'alma-checkout-plans-classic.php',
			array(
				'id'          => $gateway_id,
				'title'       => $this->gateway_helper->get_alma_gateway_title( $gateway_id ),
				'description' => $this->gateway_helper->get_alma_gateway_description( $gateway_id ),
			)
		);

		if (
			ConstantsHelper::GATEWAY_ID_IN_PAGE === $gateway_id
			|| ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW === $gateway_id
			|| ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER === $gateway_id
			|| ConstantsHelper::GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR === $gateway_id
		) {
			$this->render_fields_in_page( $eligible_plans, $gateway_id, $default_plan );
		} else {
			$this->render_fields_classic( $eligibilities, $eligible_plans, $gateway_id, $default_plan );
		}
	}


	/**
	 * Render the fields.
	 *
	 * @param array  $eligible_plans The eligible plans.
	 * @param string $gateway_id The gateway id.
	 * @param string $default_plan  The default plans.
	 * @return void
	 * @throws Exceptions\AlmaException Exception.
	 */
	public function render_fields_in_page( $eligible_plans, $gateway_id, $default_plan = null ) {
		foreach ( $eligible_plans[ $gateway_id ] as $plan_key ) {
			$this->template_loader->get_template(
				'alma-checkout-plan-in-page.php',
				array(
					'id'                   => $gateway_id,
					'logo_text'            => $this->gateway_helper->get_alma_gateway_logo_text( $gateway_id ),
					'plan_key'             => $plan_key,
					'is_checked'           => $plan_key === $default_plan,
					'plan_class'           => '.' . ConstantsHelper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS,
					'plan_id'              => '#' . sprintf( ConstantsHelper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key ),
					'logo_url'             => AssetsHelper::get_asset_url( sprintf( 'images/%s_logo.svg', $plan_key ) ),
					'upon_trigger_enabled' => $this->alma_settings->payment_upon_trigger_enabled,
					'decimal_separator'    => $this->price_factory->get_woo_decimal_separator(),
					'thousand_separator'   => $this->price_factory->get_woo_thousand_separator(),
					'decimals'             => $this->price_factory->get_woo_decimals(),
				),
				'partials'
			);
		}

		echo '<div id="alma-inpage-' . esc_html( $gateway_id ) . '"></div>';
		echo '</div>';
	}

	/**
	 * Render the fields.
	 *
	 * @param array  $eligibilities The eligibilities.
	 * @param array  $eligible_plans The eligible plans.
	 * @param string $gateway_id The gateway id.
	 * @param string $default_plan  The default plans.
	 * @return void
	 * @throws Exceptions\AlmaException Exception.
	 */
	public function render_fields_classic( $eligibilities, $eligible_plans, $gateway_id, $default_plan = null ) {
		foreach ( $eligible_plans[ $gateway_id ] as $plan_key ) {
			$this->template_loader->get_template(
				'alma-checkout-plan.php',
				array(
					'id'                   => $gateway_id,
					'logo_text'            => $this->gateway_helper->get_alma_gateway_logo_text( $gateway_id ),
					'plan_key'             => $plan_key,
					'is_checked'           => $plan_key === $default_plan,
					'plan_class'           => '.' . ConstantsHelper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS,
					'plan_id'              => '#' . sprintf( ConstantsHelper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key ),
					'logo_url'             => AssetsHelper::get_asset_url( sprintf( 'images/%s_logo.svg', $plan_key ) ),
					'upon_trigger_enabled' => $this->alma_settings->payment_upon_trigger_enabled,
				),
				'partials'
			);
		}

		$this->template_loader->get_template(
			'alma-checkout-plan-details.php',
			array(
				'alma_eligibilities'   => $eligibilities,
				'alma_default_plan'    => $default_plan,
				'alma_gateway_id'      => $gateway_id,
				'alma_settings'        => $this->alma_settings,
				'upon_trigger_enabled' => $this->alma_settings->payment_upon_trigger_enabled,
			)
		);

	}

	/**
	 * Orders the plans.
	 *
	 * @param array  $eligible_plans The plans.
	 * @param string $gateway_id The Gateway id.
	 * @return array    The sorted plans.
	 */
	public function order_plans( $eligible_plans = array(), $gateway_id = null ) {
		$eligible_plans_by_type = array(
			ConstantsHelper::GATEWAY_ID_PAY_NOW        => array(),
			ConstantsHelper::GATEWAY_ID                => array(),
			ConstantsHelper::GATEWAY_ID_PAY_LATER      => array(),
			ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR => array(),
		);

		if (
			! empty( $this->alma_settings->settings['display_in_page'] )
			&& 'yes' === $this->alma_settings->settings['display_in_page']
		) {
			unset( $eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID ] );
			unset( $eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID_PAY_NOW ] );
			unset( $eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID_PAY_LATER ] );
			unset( $eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR ] );

			$eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID_IN_PAGE ]                = array();
			$eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW ]        = array();
			$eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER ]      = array();
			$eligible_plans_by_type[ ConstantsHelper::GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR ] = array();
		}

		$result = array();

		foreach ( $eligible_plans_by_type as $type => $data ) {
			foreach ( $eligible_plans as $plan ) {
				if ( $this->alma_settings->should_display_plan( $plan, $type ) ) {
					$result[ $type ][] = $plan;
				}
			}
		}

		if ( null !== $gateway_id ) {
			if ( ! isset( $result[ $gateway_id ] ) ) {
				return array();
			}

			return $result[ $gateway_id ];
		}

		return $result;
	}

	/**
	 * Get the plans by keys.
	 *
	 * @param array $eligible_plans Eligible plans.
	 * @param array $eligibilities Eligibilities.
	 * @return array
	 */
	public function get_plans_by_keys( $eligible_plans = array(), $eligibilities = array() ) {
		$result = array();
		foreach ( $eligible_plans as $plan_key ) {
			if ( isset( $eligibilities[ $plan_key ] ) ) {
				$result[ $plan_key ] = $eligibilities[ $plan_key ];
			}
		}

		return $result;
	}
}
