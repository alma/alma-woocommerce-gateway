<?php
/**
 * FormHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Admin/Helpers
 * @namespace Alma\Woocommerce\Admin\Helpers
 */

namespace Alma\Woocommerce\Admin\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Builders\Helpers\SettingsHelperBuilder;
use Alma\Woocommerce\Helpers\InternationalizationHelper;
use Alma\Woocommerce\Helpers\SettingsHelper;

/**
 * FormHelper.
 */
class FormHelper {

	/**
	 * The form fields helper
	 *
	 * @var FormFieldsHelper
	 */
	protected $form_fields_helper;


	/**
	 * The Settings Helper.
	 *
	 * @var SettingsHelper
	 */
	protected $settings_helper;

	/**
	 * The internationalization Helper.
	 *
	 * @var InternationalizationHelper
	 */
	protected $internationalization_helper;


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->form_fields_helper          = new FormFieldsHelper();
		$this->internationalization_helper = new InternationalizationHelper();

		$settings_helper_builder = new SettingsHelperBuilder();
		$this->settings_helper   = $settings_helper_builder->get_instance();
	}

	/**
	 * Admin Form fields initialization.
	 *
	 * @param bool $show_alma_fee_plans Do we show the fee plans.
	 *
	 * @return array[]
	 */
	public function init_form_fields( $show_alma_fee_plans ) {
		$default_settings = $this->settings_helper->default_settings();

		if ( ! $show_alma_fee_plans ) {
			return array_merge(
				$this->form_fields_helper->init_enabled_field( $default_settings ),
				$this->form_fields_helper->init_api_key_fields( __( '→ Start by filling in your API keys', 'alma-gateway-for-woocommerce' ), $default_settings ),
				$this->form_fields_helper->init_debug_fields( $default_settings )
			);
		}

		return array_merge(
			$this->form_fields_helper->init_enabled_field( $default_settings ),
			$this->form_fields_helper->init_inpage_fields( $default_settings ),
			$this->form_fields_helper->init_fee_plans_fields( $default_settings ),
			$this->form_fields_helper->init_general_settings_fields( $default_settings ),
			$this->form_fields_helper->init_payment_upon_trigger_fields( $default_settings ),
			$this->form_fields_helper->init_api_key_fields( __( '→ API configuration', 'alma-gateway-for-woocommerce' ), $default_settings ),
			$this->form_fields_helper->init_share_of_checkout_field( $default_settings ),
			$this->form_fields_helper->init_technical_fields( $default_settings ),
			$this->form_fields_helper->init_debug_fields( $default_settings )
		);
	}
}
