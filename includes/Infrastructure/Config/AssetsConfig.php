<?php

namespace Alma\Gateway\Infrastructure\Config;

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;

class AssetsConfig {

	public const ASSETS_CONFIG_WIDGET = 'widget';
	public const ASSETS_CONFIG_IN_PAGE = 'in-page';
	public const ASSETS_CONFIG_WIDGET_BLOCK = 'widget-block';
	public const ASSETS_CONFIG_WIDGET_BLOCK_EDITOR = 'widget-block-editor';
	public const ASSETS_CONFIG_GATEWAY_BLOCK = 'gateway-block';
	public const ASSETS_CONFIG_ADMIN = 'admin';

	public static function getAll() {
		return array_merge(
			self::assetsConfigAdmin(),
			self::assetsConfigInPage(),
			self::assetsConfigWidget(),
			self::assetsConfigGatewayBlock(),
			self::assetsConfigWidgetBlock(),
			self::assetsConfigWidgetBlockEditor()
		);
	}

	private static function assetsConfigWidgetBlock(): array {
		return [
			self::ASSETS_CONFIG_WIDGET_BLOCK => array(
				'php'     => array(
					'src' => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.asset.php' ),
				),
				'styles'  => array(
					'alma-frontend-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css',
						'deps' => array(),
					),
					'alma-block-integration-css'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.css' ),
						'deps' => array( 'alma-frontend-widget-block-cdn' ),
					),
					'alma-widget-block-frontend'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.css' ),
						'deps' => array( 'alma-frontend-widget-block-cdn' ),
					),
				),
				'scripts' => array(
					'alma-frontend-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
						'deps' => array(),
					),
					'alma-widget-block-frontend'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.js' ),
						'deps' => array(
							'wp-blocks',
							'wp-element',
							'wp-i18n',
							'wp-components',
							'wp-editor',
							'alma-frontend-widget-block-cdn',
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
					'alma-frontend-in-page-cdn'            => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/in-page@2.x/dist/index.umd.js',
						'deps' => array(),
					),
					'alma-frontend-in-page-implementation' => array(
						'src'    => AssetsHelper::getAssetUrl( 'js/frontend/alma-frontend-in-page-implementation.js' ),
						'deps'   => array(
							'jquery',
							'alma-frontend-in-page-cdn',
						),
						'params' => array(
							'object_name' => 'alma_in_page_settings',
							'keys'        => array(
								'environment',
								'merchant_id',
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
					'alma-editor-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css',
						'deps' => array(),
					),
					'alma-block-integration-css'   => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.css' ),
						'deps' => array( 'alma-editor-widget-block-cdn' ),
					),
					'alma-widget-block-editor'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.css' ),
						'deps' => array( 'alma-editor-widget-block-cdn' ),
					),
				),
				'scripts' => array(
					'alma-editor-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
						'deps' => array(),
					),
					'alma-widget-block-editor'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.js' ),
						'deps' => array(
							'wp-blocks',
							'wp-element',
							'wp-i18n',
							'wp-components',
							'wp-editor',
							'alma-editor-widget-block-cdn'
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
					'alma-frontend-widget-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css',
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-frontend-widget-cdn'            => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
						'deps' => array(),
					),
					'alma-frontend-widget-implementation' => array(
						'src'    => AssetsHelper::getAssetUrl( 'js/frontend/alma-frontend-widget-implementation.js' ),
						'deps'   => array( 'jquery', 'alma-frontend-widget-cdn' ),
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
					'alma-block-integration-css'                 => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-gateway-block/alma-gateway-block.css' ),
						'deps' => array(),
					),
					'alma-block-integration-react-component-css' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-gateway-block/style-alma-gateway-block.css' ),
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-block-integration' => array(
						'src'          => AssetsHelper::getBuildUrl( 'alma-gateway-block/alma-gateway-block.js' ),
						'deps'         => array(
							'jquery',
							'jquery-ui-core',
							'wc-blocks-data-store',
							'wc-blocks-registry',
							'wc-settings',
							'wp-element',
							'wp-html-entities',
							'wp-i18n'
						),
						'params'       => array(
							'object_name' => 'BlocksData',
							'keys'        => array(
								'checkout_url',
								'init_eligibility',
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

	private static function assetsConfigAdmin(): array {
		return [
			self::ASSETS_CONFIG_ADMIN => array(
				'styles'  => array(
					'alma-backend-widget-block-editor-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css',
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-backend-widget-block-editor-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
						'deps' => array(),
					),
					'alma-backend'                         => array(
						'src'  => AssetsHelper::getAssetUrl( 'js/backend/alma-backend.js' ),
						'deps' => array( 'alma-backend-widget-block-editor-cdn' ),
					),
				),
			)
		];
	}
}
