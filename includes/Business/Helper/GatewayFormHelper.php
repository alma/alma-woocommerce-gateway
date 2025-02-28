<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\Plugin;

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
				'title'   => L10nHelper::__( 'Enable/Disable' ),
				'type'    => 'checkbox',
				'label'   => L10nHelper::__( 'Enable monthly payments with Alma' ),
				'default' => 'yes',
			),
		);
	}

	public function api_key_fieldset() {

		return array(
			'keys_section' => array(
				'title'       => '<hr>' . L10nHelper::__( '→ Start by filling in your API keys' ),
				'type'        => 'title',
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

	public function debug_fieldset() {
		/** @var AssetsHelper $assets_helper */
		$assets_helper = Plugin::get_container()->get( AssetsHelper::class );

		return array(
			'debug_section' => array(
				'title' => '<hr>' . L10nHelper::__( '→ Debug options', 'alma-gateway-for-woocommerce' ),
				'type'  => 'title',
			),
			'debug'         => array(
				'title'       => L10nHelper::__( 'Debug mode', 'alma-gateway-for-woocommerce' ),
				'type'        => 'checkbox',
				// translators: %s: Admin logs url.
				'label'       => L10nHelper::__( 'Activate debug mode',
						'alma-gateway-for-woocommerce' ) . sprintf( __( '(<a href="%s">Go to logs</a>)',
						'alma-gateway-for-woocommerce' ), $assets_helper->get_admin_logs_url() ),
				// translators: %s: The previous plugin version if exists.
				'description' => L10nHelper::__( 'Enable logging info and errors to help debug any issue with the plugin (previous Alma version)',
					'alma-gateway-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => 'yes',
			),
		);
	}

	public function l10n_fieldset() {
		return array(
			'l10n_section' => array(
				'title'       => '<hr>' . L10nHelper::__( '→ Localization' ),
				'type'        => 'title',
				'description' => L10nHelper::__( 'Where\'s Alma is available?</a>' ),
			)
		);
	}
}
