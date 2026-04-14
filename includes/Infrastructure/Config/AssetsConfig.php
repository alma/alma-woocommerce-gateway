<?php

namespace Alma\Gateway\Infrastructure\Config;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;

class AssetsConfig {

	public const ASSETS_CONFIG_CDN = 'cdn';
	public const ASSETS_CONFIG_WIDGET = 'widget';
	public const ASSETS_CONFIG_IN_PAGE = 'in-page';
	public const ASSETS_CONFIG_WIDGET_BLOCK = 'widget-block';
	public const ASSETS_CONFIG_WIDGET_BLOCK_EDITOR = 'widget-block-editor';
	public const ASSETS_CONFIG_GATEWAY_BLOCK = 'gateway-block';
	public const ASSETS_CONFIG_CLASSIC_CHECKOUT = 'classic-checkout';
	public const ASSETS_CONFIG_ADMIN = 'admin';
	public const CDN_WIDGET_VERSION = '4.x.x';
	public const CDN_IN_PAGE_VERSION = '2.x';

	public static function getAll(): array {
		return array_merge(
			self::assetsConfigCdn(),
			self::assetsConfigAdmin(),
			self::assetsConfigInPage(),
			self::assetsConfigWidget(),
			self::assetsConfigGatewayBlock(),
			self::assetsConfigClassicCheckout(),
			self::assetsConfigWidgetBlock(),
			self::assetsConfigWidgetBlockEditor()
		);
	}

	private static function assetsConfigCdn(): array {
		return [
			self::ASSETS_CONFIG_CDN => array(
				'styles'  => array(
					'alma-widget-cdn' => array(
						'src' => sprintf(
							'https://cdn.jsdelivr.net/npm/@alma/widgets@%s/dist/widgets.min.css',
							self::CDN_WIDGET_VERSION
						),
					),
				),
				'scripts' => array(
					'alma-widget-cdn'  => array(
						'src' => sprintf(
							'https://cdn.jsdelivr.net/npm/@alma/widgets@%s/dist/widgets.umd.js',
							self::CDN_WIDGET_VERSION
						),
					),
					'alma-in-page-cdn' => array(
						'src' => sprintf(
							'https://cdn.jsdelivr.net/npm/@alma/in-page@%s/dist/index.umd.js',
							self::CDN_IN_PAGE_VERSION
						),
					),
				),
			),
		];
	}

	private static function assetsConfigWidgetBlock(): array {
		return [
			self::ASSETS_CONFIG_WIDGET_BLOCK => array(
				'php'     => array(
					'src' => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.asset.php' ),
				),
				'styles'  => array(
					'alma-widget-block'      => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.css' ),
						'deps' => array( 'alma-widget-cdn' ),
					),
					'alma-widget-block-view' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.css' ),
						'deps' => array( 'alma-widget-cdn' ),
					),
				),
				'scripts' => array(
					'alma-widget-block' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.js' ),
						'deps' => array(
							'alma-widget-cdn',
							'wp-element',
							'wp-blocks',
							'wc-blocks-registry',
							'wp-i18n',
							'wp-components',
							'wp-editor',
							'wc-blocks-data-store',
						),
					),
				),
			)
		];
	}

	private static function assetsConfigInPage(): array {
		return [
			self::ASSETS_CONFIG_IN_PAGE => array(
				'scripts' => array(
					'alma-in-page' => array(
						'src'    => AssetsHelper::getAssetUrl( 'js/frontend/alma-in-page.js' ),
						'deps'   => array(
							'alma-in-page-cdn',
							'jquery',
						),
						'params' => array(
							'object_name' => 'alma_in_page_settings',
							'keys'        => array(
								'environment',
								'merchant_id',
								'number_decimals',
								'language',
							),
						),
					),
				),
			)
		];
	}

	private static function assetsConfigWidgetBlockEditor(): array {
		return [
			self::ASSETS_CONFIG_WIDGET_BLOCK_EDITOR => array(
				'php'     => array(
					'src' => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.asset.php' ),
				),
				'styles'  => array(
					'alma-widget-block'      => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.css' ),
						'deps' => array( 'alma-widget-cdn' ),
					),
					'alma-widget-block-view' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.css' ),
						'deps' => array( 'alma-widget-cdn' ),
					),
				),
				'scripts' => array(
					'alma-widget-block' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.js' ),
						'deps' => array(
							'wp-element',
							'wp-blocks',
							'wc-blocks-registry',
							'wp-i18n',
							'wp-components',
							'wp-editor',
							'alma-widget-cdn'
						),
					),
				),
			)
		];
	}

	private static function assetsConfigWidget(): array {
		return [
			self::ASSETS_CONFIG_WIDGET => array(
				'styles'  => array(
					'alma-widget' => array(
						'src'  => AssetsHelper::getAssetUrl( 'css/frontend/alma-widget.css' ),
						'deps' => array( 'alma-widget-cdn' ),
					),
				),
				'scripts' => array(
					'alma-widget' => array(
						'src'    => AssetsHelper::getAssetUrl( 'js/frontend/alma-widget.js' ),
						'deps'   => array(
							'alma-widget-cdn',
							'jquery',
						),
						'params' => array(
							'object_name' => 'alma_widget_settings',
							'keys'        => array(
								'environment',
								'widget_selector',
								'widget_default_selector',
								'merchant_id',
								'price',
								'fee_plan_list',
								'hide_if_not_eligible',
								'transition_delay',
								'monochrome',
								'hide_border',
								'language',
							),
						),
					),
				),
			)
		];
	}

	private static function assetsConfigGatewayBlock(): array {
		return [
			self::ASSETS_CONFIG_GATEWAY_BLOCK => array(
				'php'     => array(
					'src' => AssetsHelper::getBuildUrl( 'alma-gateway-block/alma-gateway-block.asset.php' ),
				),
				'styles'  => array(
					'alma-gateway-block'                 => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-gateway-block/alma-gateway-block.css' ),
						'deps' => array(),
					),
					'alma-gateway-block-react-component' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-gateway-block/style-alma-gateway-block.css' ),
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-gateway-block' => array(
						'src'          => AssetsHelper::getBuildUrl( 'alma-gateway-block/alma-gateway-block.js' ),
						'deps'         => array(
							'jquery',
							'jquery-ui-core',
							'wp-element',
							'wp-blocks',
							'wc-blocks-registry',
							'wc-blocks-data-store',
							'wc-settings',
							'wp-html-entities',
							'wp-i18n'
						),
						'params'       => array(
							'object_name' => 'AlmaInitSettings',
							'keys'        => array(
								'checkout_url',
								'gateway_settings',
								'cart_total',
								'nonce_value',
								'label_button',
								'is_in_page',
								'merchant_id',
								'environment',
								'language',
								'ajax_url',
							),
						),
						'translations' => array(
							'domain' => L10nHelper::ALMA_L10N_DOMAIN,
							'path'   => AssetsHelper::getLanguagesPath(),
						),
					),
				),
			),
		];
	}

	private static function assetsConfigClassicCheckout(): array {
		return [
			self::ASSETS_CONFIG_CLASSIC_CHECKOUT => array(
				'styles'  => array(
					'alma-classic-checkout' => array(
						'src'  => AssetsHelper::getAssetUrl( 'css/frontend/alma-checkout.css' ),
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-classic-checkout' => array(
						'src'          => AssetsHelper::getAssetUrl( 'js/frontend/alma-checkout.js' ),
						'deps'         => array(
							'jquery',
							'jquery-ui-core',
						),
						'translations' => array(
							'domain' => L10nHelper::ALMA_L10N_DOMAIN,
							'path'   => AssetsHelper::getLanguagesPath(),
						),
					),
				),
			),
		];
	}

	private static function assetsConfigAdmin(): array {
		return [
			self::ASSETS_CONFIG_ADMIN => array(
				'scripts' => array(
					'alma-admin' => array(
						'src' => AssetsHelper::getAssetUrl( 'js/backend/alma-admin.js' ),
					),
				),
			)
		];
	}
}
