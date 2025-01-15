<?php
/**
 * ConstantsHelper.
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

/**
 * ConstantsHelper
 */
class ConstantsHelper {

	const GATEWAY_ID                        = 'alma';
	const GATEWAY_ID_PAY_NOW                = 'alma_pay_now';
	const GATEWAY_ID_PAY_LATER              = 'alma_pay_later';
	const GATEWAY_ID_MORE_THAN_FOUR         = 'alma_pnx_plus_4';
	const GATEWAY_ID_IN_PAGE                = 'alma_in_page';
	const GATEWAY_ID_IN_PAGE_PAY_NOW        = 'alma_in_page_pay_now';
	const GATEWAY_ID_IN_PAGE_PAY_LATER      = 'alma_in_page_pay_later';
	const GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR = 'alma_in_page_pnx_plus_4';

	const JQUERY_CART_UPDATE_EVENT = 'updated_cart_totals';

	const ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE = 'alma-payment-plan-table-%s-installments';
	const ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS   = 'js-alma-payment-plan-table';

	const PREFIX_REFUND_COMMENT     = 'Refund made via WooCommerce back-office - ';
	const FLAG_ORDER_FULLY_REFUNDED = 'alma_order_fully_refunded';

	const SUCCESS                        = 'success';
	const REFUND_NOTICE_META_KEY         = 'alma_refund_notices';
	const CART_SHORTCODE_TAG             = 'alma-cart-eligibility';
	const PRODUCT_SHORTCODE_TAG          = 'alma-product-eligibility';
	const CUSTOMER_RETURN                = 'alma_customer_return';
	const IPN_CALLBACK                   = 'alma_ipn_callback';
	const CHECKOUT_NONCE                 = 'alma_checkout_nonce';
	const ALMA_FEE_PLAN                  = 'alma_fee_plan';
	const ALMA_FEE_PLAN_IN_PAGE          = 'alma_fee_plan_in_page';
	const DEFAULT_FEE_PLAN               = 'general_3_0_0';
	const PAY_NOW_FEE_PLAN               = 'general_1_0_0';
	const PAYMENT_METHOD                 = 'payment_method';
	const PAYMENT_METHOD_TITLE           = 'payment_method_title';
	const DEFAULT_CHECK_VARIATIONS_EVENT = 'check_variations';
	const AMOUNT_PLAN_KEY_REGEX          = '#^(min|max)_amount_general_[0-9]{1,2}_[0-9]{1,2}_[0-9]{1,2}$#';
	const SORT_PLAN_KEY_REGEX            = '/^(general|pos)_([0-9]{1,2})_([0-9]{1,2})_([0-9]{1,2})$/';

	const NOTICE_NONCE_NAME                             = 'wc_alma_notice_nonce';
	const ALMA_LOGO_PATH                                = 'images/alma_logo.svg';
	const ALMA_SHORT_LOGO_PATH                          = 'images/alma_short_logo.svg';
	const ALMA_PATH_CHECKOUT_JS                         = 'js/alma-checkout.js';
	const ALMA_PATH_CHECKOUT_BLOCK_JS                   = 'alma-checkout-blocks.js';
	const ALMA_PATH_CHECKOUT_BLOCK_CSS                  = 'alma-checkout-blocks.css';
	const ALMA_PATH_CHECKOUT_BLOCK_REACT_COMPONENTS_CSS = 'style-alma-checkout-blocks.css';
	const ALMA_PATH_CHECKOUT_BLOCK_PHP                  = 'alma-checkout-blocks.asset.php';
	const ALMA_PATH_CHECKOUT_IN_PAGE_JS                 = 'js/alma-checkout-in-page.js';
	const ALMA_PATH_CHECKOUT_CDN_IN_PAGE_JS             = 'https://cdn.jsdelivr.net/npm/@alma/in-page@2.x/dist/index.umd.js';
	const ALMA_PATH_CHECKOUT_CSS                        = 'css/alma-checkout.css';
	const PAY_IN_INSTALLMENTS                           = 'Pay in installments';
	const PAY_LATER                                     = 'Pay later';
	const PAY_BY_FINANCING                              = 'Pay with financing';

	const PAY_NOW = 'Pay by credit card';

	const ERROR = 'error';

	/**
	 * The payment status
	 *
	 * @var string[]
	 */
	public static $payment_statuses = array(
		'on-hold',
		'pending',
		'failed',
		'cancelled',
	);

	/**
	 * The gateways.
	 *
	 * @var string[]
	 */
	public static $gateways_ids = array(
		self::GATEWAY_ID,
		self::GATEWAY_ID_IN_PAGE,
		self::GATEWAY_ID_PAY_NOW,
		self::GATEWAY_ID_IN_PAGE_PAY_NOW,
		self::GATEWAY_ID_PAY_LATER,
		self::GATEWAY_ID_IN_PAGE_PAY_LATER,
		self::GATEWAY_ID_MORE_THAN_FOUR,
		self::GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR,
	);

	/**
	 * The gateways.
	 *
	 * @var string[]
	 */
	public static $gateways_in_page_ids = array(
		self::GATEWAY_ID_IN_PAGE,
		self::GATEWAY_ID_IN_PAGE_PAY_NOW,
		self::GATEWAY_ID_IN_PAGE_PAY_LATER,
		self::GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR,
	);
}
