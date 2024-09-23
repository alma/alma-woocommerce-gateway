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

use Alma\API\Lib\PaymentValidator;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\CoreFactory;
use Alma\Woocommerce\Factories\CurrencyFactory;
use Alma\Woocommerce\Factories\CustomerFactory;
use Alma\Woocommerce\Factories\PHPFactory;
use Alma\Woocommerce\Factories\PluginFactory;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Factories\SessionFactory;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\AssetsHelper;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\CheckoutHelper;
use Alma\Woocommerce\Helpers\CustomerHelper;
use Alma\Woocommerce\Helpers\GatewayHelper;
use Alma\Woocommerce\Helpers\InternationalizationHelper;
use Alma\Woocommerce\Helpers\PaymentHelper;
use Alma\Woocommerce\Helpers\PHPHelper;
use Alma\Woocommerce\Helpers\ProductHelper;
use Alma\Woocommerce\Helpers\TemplateLoaderHelper;
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

		return new CustomerFactory( new PHPFactory() );
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

	/**
	 *  PHPFactory.
	 *
	 * @param PHPFactory $php_factory The PHP factory.
	 *
	 * @return PHPFactory|null
	 */
	public function get_php_factory( $php_factory = null ) {
		if ( $php_factory ) {
			return $php_factory;
		}

		return new PHPFactory();
	}

	/**
	 *  The core factory.
	 *
	 * @param CoreFactory $core_factory The core factory.
	 *
	 * @return CoreFactory|null
	 */
	public function get_core_factory( $core_factory = null ) {
		if ( $core_factory ) {
			return $core_factory;
		}

		return new CoreFactory();
	}

	/**
	 * GatewayHelper.
	 *
	 * @param GatewayHelper|null $gateway_helper The gateway helper.
	 *
	 * @return GatewayHelper
	 */
	public function get_gateway_helper( $gateway_helper = null ) {
		if ( $gateway_helper ) {
			return $gateway_helper;
		}

		return new GatewayHelper(
			$this->get_alma_settings(),
			$this->get_payment_helper(),
			$this->get_checkout_helper(),
			$this->get_cart_factory(),
			$this->get_product_helper(),
			$this->get_core_factory(),
			$this->get_cart_helper(),
			$this->get_php_helper()
		);
	}

	/**
	 * TemplateLoaderHelper.
	 *
	 * @param TemplateLoaderHelper|null $template_loader_helper The template loader helper.
	 *
	 * @return TemplateLoaderHelper
	 */
	public function get_template_loader_helper( $template_loader_helper = null ) {
		if ( $template_loader_helper ) {
			return $template_loader_helper;
		}

		return new TemplateLoaderHelper();
	}

	/**
	 * PaymentHelper.
	 *
	 * @param PaymentHelper|null $payment_helper The payment helper.
	 *
	 * @return PaymentHelper
	 */
	public function get_payment_helper( $payment_helper = null ) {
		if ( $payment_helper ) {
			return $payment_helper;
		}

		return new PaymentHelper();
	}

	/**
	 * CheckoutHelper.
	 *
	 * @param CheckoutHelper|null $checkout_helper The checkout helper.
	 *
	 * @return CheckoutHelper
	 */
	public function get_checkout_helper( $checkout_helper = null ) {
		if ( $checkout_helper ) {
			return $checkout_helper;
		}

		return new CheckoutHelper();
	}

	/**
	 * ProductHelper.
	 *
	 * @param ProductHelper|null $product_helper The product helper.
	 *
	 * @return ProductHelper
	 */
	public function get_product_helper( $product_helper = null ) {
		if ( $product_helper ) {
			return $product_helper;
		}

		return new ProductHelper(
			$this->get_alma_logger(),
			$this->get_alma_settings(),
			$this->get_cart_factory(),
			$this->get_core_factory()
		);
	}
	/**
	 * CartHelper.
	 *
	 * @param CartHelper|null $cart_helper The cart helper.
	 *
	 * @return CartHelper
	 */
	public function get_cart_helper( $cart_helper = null ) {
		if ( $cart_helper ) {
			return $cart_helper;
		}

		return new CartHelper(
			$this->get_tools_helper(),
			$this->get_session_factory(),
			$this->get_version_factory(),
			$this->get_cart_factory(),
			$this->get_alma_settings(),
			$this->get_alma_logger(),
			$this->get_customer_helper()
		);
	}

	/**
	 * PHPHelper.
	 *
	 * @param PHPHelper|null $php_helper The php helper.
	 *
	 * @return PHPHelper
	 */
	public function get_php_helper( $php_helper = null ) {
		if ( $php_helper ) {
			return $php_helper;
		}

		return new PHPHelper();
	}

	/**
	 * PaymentValidator.
	 *
	 * @return PaymentValidator
	 */
	public function get_payment_validator() {
		return new PaymentValidator();
	}

}
