<?php
/**
 * Alma_Plan_Builder.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Models\Alma_Cart;

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
	 * Constructor.
	 */
	public function __construct() {
		$this->alma_settings  = new Alma_Settings();
		$this->gateway_helper = new Alma_Gateway_Helper();
	}

	/**
	 * Render the checkout fields.
	 *
	 * @param array  $eligibilities The eligitibilies.
	 * @param array  $eligible_plans The eligibles plans.
	 * @param string $default_plan The default plan.
	 * @return void
	 */
	public function render_checkout_fields( $eligibilities, $eligible_plans, $default_plan = null ) {

		if ( empty( $eligible_plans ) ) {
			$templates = new Alma_Template_Loader();
			$templates->get_template( 'alma-checkout-no-plans.php' );

			return;
		}

		$this->render_fields( $eligibilities, $eligible_plans, $default_plan );
	}

	/**
	 * Render the fields.
	 *
	 * @param array  $eligibilities The eligibilities.
	 * @param array  $eligible_plans The eligible plans.
	 * @param string $default_plan  The default plans.
	 * @return void
	 * @throws Exceptions\Alma_Exception Exception.
	 */
	public function render_fields( $eligibilities, $eligible_plans, $default_plan = null ) {
		$templates              = new Alma_Template_Loader();
		$eligible_plans_by_type = $this->order_plans( $eligible_plans );

		if ( 0 === count( $eligible_plans_by_type ) ) {
			return;
		}

		echo '<div id="alma_plans_accordion">';

		foreach ( $eligible_plans_by_type as $type => $eligible_plans ) {
			$templates->get_template(
				'alma-checkout-plans.php',
				array(
					'id'          => $type,
					'title'       => $this->gateway_helper->get_alma_gateway_title( $type ),
					'description' => $this->gateway_helper->get_alma_gateway_description( $type ),
				)
			);

			foreach ( $eligible_plans as $plan_key ) {
				$templates->get_template(
					'alma-checkout-plan.php',
					array(
						'id'                   => $type,
						'logo_text'            => $this->gateway_helper->get_alma_gateway_logo_text( $type ),
						'plan_key'             => $plan_key,
						'is_checked'           => $plan_key === $default_plan,
						'plan_class'           => '.' . Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS,
						'plan_id'              => '#' . sprintf( Alma_Constants_Helper::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan_key ),
						'logo_url'             => Alma_Assets_Helper::get_asset_url( sprintf( "images/%s_logo.svg", $plan_key ) ),
						'upon_trigger_enabled' => $this->alma_settings->payment_upon_trigger_enabled,
					),
					'partials'
				);
			}
			echo '</div>';
		}
		echo '</div>';

		$templates->get_template(
			'alma-checkout-plan-details.php',
			array(
				'alma_eligibilities'   => $eligibilities,
				'alma_default_plan'    => $default_plan,
				'alma_gateway_id'      => $type,
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
			Alma_Constants_Helper::ALMA_GATEWAY_PAY_NOW   => array(),
			Alma_Constants_Helper::GATEWAY_ID             => array(),
			Alma_Constants_Helper::ALMA_GATEWAY_PAY_LATER => array(),
			Alma_Constants_Helper::ALMA_GATEWAY_PAY_MORE_THAN_FOUR => array(),
		);

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
