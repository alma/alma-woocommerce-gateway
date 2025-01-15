<?php
/**
 * GatewayHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\CartHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\ProductHelperBuilder;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\CoreFactory;
use Alma\Woocommerce\Services\PaymentUponTriggerService;

/**
 * GatewayHelper
 */
class GatewayHelper {




	/**
	 * The settings.
	 *
	 * @var SettingsHelper
	 */
	protected $alma_settings;

	/**
	 * The payment helper.
	 *
	 * @var PaymentHelper
	 */
	protected $payment_helper;

	/**
	 * The checkout helper
	 *
	 * @var CheckoutHelper
	 */
	protected $checkout_helper;

	/**
	 * The cart factory.
	 *
	 * @var CartFactory
	 */
	protected $cart_factory;


	/**
	 * The product helper.
	 *
	 * @var ProductHelper
	 */
	protected $product_helper;
	/**
	 * The core factory.
	 *
	 * @var CoreFactory
	 */
	protected $core_factory;


	/**
	 * The cart helper.
	 *
	 * @var CartHelper
	 */
	protected $cart_helper;

	/**
	 * The php helper.
	 *
	 * @var PHPHelper
	 */
	protected $php_helper;


	/**
	 * Constructor.
	 *
	 * @param AlmaSettings   $alma_settings  The settings.
	 * @param PaymentHelper  $payment_helper The payment helper.
	 * @param CheckoutHelper $checkout_helper The checkout helper.
	 * @param CartFactory    $cart_factory The factory cart.
	 * @param ProductHelper  $product_helper The product helper.
	 * @param CoreFactory    $core_factory The core factory.
	 * @param CartHelper     $cart_helper The cart helper.
	 * @param PHPHelper      $php_helper The php helper.
	 */
	public function __construct( $alma_settings, $payment_helper, $checkout_helper, $cart_factory, $product_helper, $core_factory, $cart_helper, $php_helper ) {
		$this->alma_settings   = $alma_settings;
		$this->payment_helper  = $payment_helper;
		$this->checkout_helper = $checkout_helper;
		$this->cart_factory    = $cart_factory;
		$this->product_helper  = $product_helper;
		$this->core_factory    = $core_factory;
		$this->cart_helper     = $cart_helper;
		$this->php_helper      = $php_helper;
	}

	/**
	 * Filter available_gateways to add  "alma", "alma_pay_later" and "alma_pnx_plus_4".
	 *
	 * @param array $available_gateways The list of available gateways.
	 *
	 * @return array
	 */
	public function woocommerce_available_payment_gateways( $available_gateways ) {
		if ( $this->core_factory->is_admin() ) {
			return $available_gateways;
		}

		$has_excluded_products  = $this->product_helper->cart_has_excluded_product();
		$new_available_gateways = array();

		foreach ( $available_gateways as $key => $gateway ) {

			if (
				in_array( $gateway->id, ConstantsHelper::$gateways_ids, true )
				&& $has_excluded_products
			) {
				unset( $available_gateways[ $key ] );

				return $available_gateways;
			}

			$new_available_gateways[ $key ] = $gateway;
		}

		return $new_available_gateways;
	}

	/**
	 * Filter the alma gateway title (visible on checkout page).
	 *
	 * @param string  $title The original title.
	 * @param integer $id The payment gateway id.
	 *
	 * @return string
	 */
	public function woocommerce_gateway_title( $title, $id ) {
		if ( in_array( $id, ConstantsHelper::$gateways_ids, true ) ) {
			return $this->alma_settings->get_title( $id );
		}

		return $title;
	}

	/**
	 * Filter the alma gateway title (visible on checkout page).
	 *
	 * @param string  $title The title.
	 * @param integer $id The payment gateway id.
	 *
	 * @return string
	 */
	public function woocommerce_gateway_description( $title, $id ) {

		if ( in_array( $id, ConstantsHelper::$gateways_ids, true ) ) {
			return $this->alma_settings->get_description( $id );
		}

		return $title;
	}

	/**
	 * Get the title of the Alma Gateway.
	 *
	 * @param string  $id The alma gateway type id.
	 * @param boolean $is_blocks Are we in blocks.
	 * @return string
	 * @throws AlmaException Exception.
	 */
	public function get_alma_gateway_title( $id, $is_blocks = false ) {
		if ( in_array( $id, ConstantsHelper::$gateways_ids, true ) ) {
			return $this->alma_settings->get_title( $id, $is_blocks );
		}

		throw new AlmaException( sprintf( 'Unknown gateway id : %s', $id ) );
	}

	/**
	 * Get the title to replace the icon
	 *
	 * @param string $id The alma gateway type id.
	 * @return string
	 */
	public function get_alma_gateway_logo_text( $id ) {
		if (
			ConstantsHelper::GATEWAY_ID_PAY_NOW === $id
			|| ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW === $id
		) {

			return __( 'Pay Now', 'alma-gateway-for-woocommerce' );
		}

		return 'null';
	}

	/**
	 * Are we in page gateway?
	 *
	 * @param string $id The gateway id.
	 *
	 * @return bool
	 */
	public function is_in_page_gateway( $id ) {
		if ( in_array( $id, ConstantsHelper::$gateways_in_page_ids, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the title of the Alma Gateway.
	 *
	 * @param string  $id The alma gateway type id.
	 * @param boolean $is_blocks Are we in blocks.
	 *
	 * @return string
	 * @throws AlmaException Exception.
	 */
	public function get_alma_gateway_description( $id, $is_blocks = false ) {
		if ( in_array( $id, ConstantsHelper::$gateways_ids, true ) ) {
			return $this->alma_settings->get_description( $id, $is_blocks );
		}

		throw new AlmaException( sprintf( 'Unknown gateway id : %s', $id ) );

	}

	/**
	 * Check if cart has eligibilities.
	 *
	 * @return bool
	 */
	public function is_there_eligibility_in_cart() {
		return count( $this->cart_helper->get_eligible_plans_keys_for_cart() ) > 0;
	}

	/**
	 * Check if there is some excluded products into cart
	 *
	 * @return bool
	 */
	public function cart_contains_excluded_category() {
		if ( $this->cart_factory->get_cart() === null ) {
			return false;
		}

		if (
			$this->php_helper->property_exists( $this->alma_settings, 'excluded_products_list' )
			&& is_array( $this->alma_settings->excluded_products_list )
			&& count( $this->alma_settings->excluded_products_list ) > 0
		) {
			$cart_items = $this->cart_factory->get_cart_items();

			foreach ( $cart_items as $cart_item ) {
				$product_id = $cart_item['product_id'];

				foreach ( $this->alma_settings->excluded_products_list as $category_slug ) {
					if ( $this->core_factory->has_term( $category_slug, 'product_cat', $product_id ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}


	/**
	 * Gets default plan according to eligible pnx list.
	 *
	 * @param array $plans the list of eligible pnx.
	 *
	 * @return string|null
	 */
	public function get_default_plan( $plans ) {
		if ( ! count( $plans ) ) {
			return null;
		}

		if ( in_array( ConstantsHelper::DEFAULT_FEE_PLAN, $plans, true ) ) {
			return ConstantsHelper::DEFAULT_FEE_PLAN;
		}

		$default_plan = array_shift( $plans );

		if ( is_array( $default_plan ) ) {
			$default_plan = $default_plan[0];
		}

		return $default_plan;
	}

	/**
	 *  Add the actions.
	 *
	 * @codeCoverageIgnore Add the actions for obsolete payment upon trigger.
	 * @return void
	 */
	public function add_actions() {
		$payment_upon_trigger_helper = new PaymentUponTriggerService();
		add_action(
			'woocommerce_order_status_changed',
			array(
				$payment_upon_trigger_helper,
				'woocommerce_order_status_changed',
			),
			10,
			3
		);
	}
}
