<?php

namespace Alma\Gateway\Infrastructure\Config;

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;

class AssetsConfig {

	public const ASSETS_CONFIG_WIDGET              = 'widget';
	public const ASSETS_CONFIG_WIDGET_BLOCK        = 'widget-block';
	public const ASSETS_CONFIG_WIDGET_BLOCK_EDITOR = 'widget-block-editor';
	public const ASSETS_CONFIG_CHECKOUT_BLOCK      = 'checkout-block';
	public const ASSETS_CONFIG_ADMIN               = 'admin';

	public static function getAll() {
		return array(

			self::ASSETS_CONFIG_WIDGET              => array(
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
						'src'       => AssetsHelper::getAssetUrl( 'js/frontend/alma-frontend-widget-implementation.js' ),
						'deps'      => array( 'jquery' ),
						'in_footer' => true,
						'params'    => array(
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
			),

			self::ASSETS_CONFIG_CHECKOUT_BLOCK      => array(
				'php'     => array(
					'src' => AssetsHelper::getBuildUrl( 'alma-checkout-block/alma-checkout-blocks.asset.php' ),
				),
				'styles'  => array(
					'alma-blocks-integration-css' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-checkout-block/alma-checkout-blocks.css' ),
						'deps' => array(),
					),
					'alma-blocks-integration-react-component-css' => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-checkout-block/style-alma-checkout-blocks.css' ),
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-blocks-integration' => array(
						'src'          => AssetsHelper::getBuildUrl( 'alma-checkout-block/alma-checkout-blocks.js' ),
						'deps'         => array(
							'jquery',
							'jquery-ui-core',
							'wc-blocks-registry',
							'wc-settings',
							'wp-element',
							'wp-html-entities',
							'wp-i18n',
						),
						'in_footer'    => true,
						'params'       => array(
							'object_name' => 'BlocksData',
							'keys'        => array(
								'url',
								'init_eligibility',
								'cart_total',
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

			self::ASSETS_CONFIG_WIDGET_BLOCK        => array(
				'php'     => array(
					'src' => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-blocks-view.asset.php' ),
				),
				'styles'  => array(
					'alma-frontend-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css',
						'deps' => array(),
					),
					'alma-blocks-integration-css'    => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-blocks.css' ),
						'deps' => array(),
					),
					'alma-widget-block-frontend'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.css' ),
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-frontend-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
						'deps' => array(),
					),
					'alma-widget-block-frontend'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.js' ),
						'deps' => array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-editor' ),
					),
				),
			),

			self::ASSETS_CONFIG_WIDGET_BLOCK_EDITOR => array(
				'php'     => array(
					'src' => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-blocks.asset.php' ),
				),
				'styles'  => array(
					'alma-editor-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css',
						'deps' => array(),
					),
					'alma-blocks-integration-css'  => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-blocks.css' ),
						'deps' => array(),
					),
					'alma-widget-block-editor'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block-view.css' ),
						'deps' => array(),
					),
				),
				'scripts' => array(
					'alma-editor-widget-block-cdn' => array(
						'src'  => 'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
						'deps' => array(),
					),
					'alma-widget-block-editor'     => array(
						'src'  => AssetsHelper::getBuildUrl( 'alma-widget-block/alma-widget-block.js' ),
						'deps' => array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-editor' ),
					),
				),
			),

			self::ASSETS_CONFIG_ADMIN               => array(
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
				),
			),
		);
	}
}
