<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Woocommerce\Helpers\AssetsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class GatewayFormHelper {

	/**
	 * Inits enabled Admin field.
	 *
	 * @return array[]
	 */
	public function enabled_field() {
		return array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'alma-gateway-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable monthly payments with Alma', 'alma-gateway-for-woocommerce' ),
				'default' => 'yes',
			),
		);
	}

	public function api_key_fieldset() {

		return array(
			'keys_section' => array(
				'title'       => '<hr>' . __( '→ Start by filling in your API keys', 'alma-gateway-for-woocommerce' ),
				'type'        => 'title',
				/* translators: %s Alma security URL */
				'description' => L10nHelper::__( 'You can find your API keys on your Alma dashboard</a>' ),
			),
			'live_api_key' => array(
				'title' => L10nHelper::__( 'Live API key' ),
				'type'  => 'password',
			),
			'test_api_key' => array(
				'title' => L10nHelper::__( 'Test API key' ),
				'type'  => 'password',
			),
			'environment'  => array(
				'title'       => L10nHelper::__( 'API Mode' ),
				'type'        => 'select',
				/* translators: %s Merchant description */
				'description' => sprintf(
					L10nHelper::__( 'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.<br> %s' ),
					'TODO: Informations marchand'
				),
				'default'     => 'test',
				'options'     => array(
					'test' => L10nHelper::__( 'Test' ),
					'live' => L10nHelper::__( 'Live' ),
				),
			),
		);
	}
}
