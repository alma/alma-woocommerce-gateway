<?php
/**
 * BuilderTrait.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Traits
 * @namespace Alma\Woocommerce\Builders
 */

namespace Alma\Woocommerce\Traits;

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\CurrencyFactory;
use Alma\Woocommerce\Factories\CustomerFactory;
use Alma\Woocommerce\Factories\PluginFactory;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Factories\SessionFactory;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\AssetsHelper;
use Alma\Woocommerce\Helpers\CustomerHelper;
use Alma\Woocommerce\Helpers\InternationalizationHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class BuilderTrait.
 */
trait BuilderTrait {

	/**
	 * Tools Helper.
	 *
	 * @param ToolsHelper|null $tools_helper The tools helper.
	 *
	 * @return ToolsHelper
	 */
	public function get_tools_helper( $tools_helper = null ) {
		if ( $tools_helper ) {
			return $tools_helper;

		}

		return new ToolsHelper(
			new AlmaLogger(),
			new PriceFactory(),
			new CurrencyFactory()
		);
	}

	/**
	 * Session Factory.
	 *
	 * @param SessionFactory|null $session_factory The session factory.
	 *
	 * @return SessionFactory
	 */
	public function get_session_factory( $session_factory = null ) {
		if ( $session_factory ) {
			return $session_factory;
		}

		return new SessionFactory();
	}

	/**
	 * Version Factory.
	 *
	 * @param VersionFactory|null $version_factory The version Factory.
	 *
	 * @return VersionFactory
	 */
	public function get_version_factory( $version_factory = null ) {
		if ( $version_factory ) {
			return $version_factory;
		}

		return new VersionFactory();
	}

	/**
	 * Cart Factory.
	 *
	 * @param CartFactory|null $cart_factory The cart factory.
	 *
	 * @return CartFactory
	 */
	public function get_cart_factory( $cart_factory = null ) {
		if ( $cart_factory ) {
			return $cart_factory;
		}

		return new CartFactory();
	}

	/**
	 * Alma Logger.
	 *
	 * @param AlmaLogger|null $alma_logger The alma logger.
	 *
	 * @return AlmaLogger
	 */
	public function get_alma_logger( $alma_logger = null ) {
		if ( $alma_logger ) {
			return $alma_logger;
		}

		return new AlmaLogger();
	}

	/**
	 * Price Factory.
	 *
	 * @param PriceFactory|null $price_factory The price factory.
	 *
	 * @return PriceFactory
	 */
	public function get_price_factory( $price_factory = null ) {
		if ( $price_factory ) {
			return $price_factory;
		}

		return new PriceFactory();
	}

	/**
	 * Currency Factory.
	 *
	 * @param CurrencyFactory|null $currency_factory The currency factory.
	 *
	 * @return CurrencyFactory
	 */
	public function get_currency_factory( $currency_factory = null ) {
		if ( $currency_factory ) {
			return $currency_factory;
		}

		return new CurrencyFactory();
	}

	/**
	 * InternationalizationHelper.
	 *
	 * @param InternationalizationHelper|null $internalionalization_helper The internalionalization helper.
	 *
	 * @return InternationalizationHelper
	 */
	public function get_internalionalization_helper( $internalionalization_helper = null ) {
		if ( $internalionalization_helper ) {
			return $internalionalization_helper;

		}

		return new InternationalizationHelper();
	}

	/**
	 * AssetHelpers.
	 *
	 * @param AssetsHelper|null $assets_helper The assets helper.
	 *
	 * @return AssetsHelper
	 */
	public function get_assets_helper( $assets_helper = null ) {
		if ( $assets_helper ) {
			return $assets_helper;

		}

		return new AssetsHelper();
	}

	/**
	 * PluginFactory.
	 *
	 * @param PluginFactory|null $plugin_factory The plugin factory.
	 *
	 * @return PluginFactory
	 */
	public function get_plugin_factory( $plugin_factory = null ) {
		if ( $plugin_factory ) {
			return $plugin_factory;

		}

		return new PluginFactory();
	}

	/**
	 * Customer Factory.
	 *
	 * @param CustomerFactory|null $customer_factory The customer factory.
	 *
	 * @return CustomerFactory
	 */
	public function get_customer_factory( $customer_factory = null ) {
		if ( $customer_factory ) {
			return $customer_factory;
		}

		return new CustomerFactory();
	}

	/**
	 * AlmaSettings.
	 *
	 * @param AlmaSettings|null $alma_settings The alma settings.
	 *
	 * @return AlmaSettings
	 */
	public function get_alma_settings( $alma_settings = null ) {
		if ( $alma_settings ) {
			return $alma_settings;
		}

		return new AlmaSettings();
	}

	/**
	 * CustomerHelper.
	 *
	 * @param CustomerHelper|null $customer_helper The customer helper.
	 *
	 * @return CustomerHelper
	 */
	public function get_customer_helper( $customer_helper = null ) {
		if ( $customer_helper ) {
			return $customer_helper;
		}

		return new CustomerHelper( $this->get_customer_factory() );
	}
}
