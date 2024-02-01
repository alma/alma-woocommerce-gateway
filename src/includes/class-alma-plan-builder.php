<?php
/**
 * Alma_Plan_Builder.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Plan_Builder
 */
class Alma_Plan_Builder {


	/**
	 * The settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;


	/**
	 * The gateway helper.
	 *
	 * @var Alma_Gateway_Helper
	 */
	protected $gateway_helper;

	/**
	 * The template loader.
	 *
	 * @var Alma_Template_Loader
	 */
	protected $template_loader;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->alma_settings   = new Alma_Settings();
		$this->gateway_helper  = new Alma_Gateway_Helper();
		$this->template_loader = new Alma_Template_Loader();
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
	 * @throws Exceptions\Alma_Exception Exception.
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
			Alma_Constants_Helper::GATEWAY_ID_IN_PAGE === $gateway_id
			|| Alma_Constants_Helper::GATEWAY_ID_IN_PAGE_PAY_NOW === $gateway_id
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
	 * @throws Exceptions\Alma_Exception Exception.
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
					'plan_class'           => '.' . Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS,
					'plan_id'              => '#' . sprintf( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key ),
					'logo_url'             => Alma_Assets_Helper::get_asset_url( sprintf( 'images/%s_logo.svg', $plan_key ) ),
					'upon_trigger_enabled' => $this->alma_settings->payment_upon_trigger_enabled,
					'decimal_separator'    => wc_get_price_decimal_separator(),
					'thousand_separator'   => wc_get_price_thousand_separator(),
					'decimals'             => wc_get_price_decimals(),
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
	 * @throws Exceptions\Alma_Exception Exception.
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
					'plan_class'           => '.' . Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS,
					'plan_id'              => '#' . sprintf( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key ),
					'logo_url'             => Alma_Assets_Helper::get_asset_url( sprintf( 'images/%s_logo.svg', $plan_key ) ),
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
	 * @param array $eligible_plans The plans.
	 * @return array    The sorted plans.
	 */
	public function order_plans( $eligible_plans = array() ) {
		$eligible_plans_by_type = array(
			Alma_Constants_Helper::GATEWAY_ID_PAY_NOW   => array(),
			Alma_Constants_Helper::GATEWAY_ID           => array(),
			Alma_Constants_Helper::GATEWAY_ID_PAY_LATER => array(),
			Alma_Constants_Helper::GATEWAY_ID_MORE_THAN_FOUR => array(),
		);

		if (
			! empty( $this->alma_settings->settings['display_in_page'] )
			&& 'yes' === $this->alma_settings->settings['display_in_page']
		) {
			unset( $eligible_plans_by_type[ Alma_Constants_Helper::GATEWAY_ID ] );
			unset( $eligible_plans_by_type[ Alma_Constants_Helper::GATEWAY_ID_PAY_NOW ] );

			$eligible_plans_by_type[ Alma_Constants_Helper::GATEWAY_ID_IN_PAGE ]         = array();
			$eligible_plans_by_type[ Alma_Constants_Helper::GATEWAY_ID_IN_PAGE_PAY_NOW ] = array();
		}

		$result = array();

		foreach ( $eligible_plans_by_type as $type => $data ) {
			foreach ( $eligible_plans as $plan ) {
				if ( $this->alma_settings->should_display_plan( $plan, $type ) ) {
					$result[ $type ][] = $plan;
				}
			}
		}

		return $result;
	}
}
