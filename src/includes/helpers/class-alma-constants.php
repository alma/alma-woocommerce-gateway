<?php
/**
 * Alma_Constants.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Constants
 */
class Alma_Constants {


	const GATEWAY_ID = 'alma';

	const ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE = 'alma-payment-plan-table-%s-installments';
	const ALMA_PAYMENT_PLAN_TABLE_CSS_CLASS   = 'js-alma-payment-plan-table';
	const ENABLED_PLAN_KEY_REGEX              = '#^enabled_general_([0-9]+_[0-9]+_[0-9]+)$#';
	const ALMA_GATEWAY_PAY_LATER              = 'alma_pay_later';
	const ALMA_GATEWAY_PAY_MORE_THAN_FOUR     = 'alma_pnx_plus_4';
	const JQUERY_CART_UPDATE_EVENT            = 'updated_cart_totals';
	const PREFIX_REFUND_COMMENT               = 'Refund made via WooCommerce back-office - ';
	const FLAG_ORDER_FULLY_REFUNDED           = 'alma_order_fully_refunded';

	const SUCCESS                        = 'success';
	const REFUND_NOTICE_META_KEY         = 'alma_refund_notices';
	const CART_SHORTCODE_TAG             = 'alma-cart-eligibility';
	const PRODUCT_SHORTCODE_TAG          = 'alma-product-eligibility';
	const CUSTOMER_RETURN                = 'alma_customer_return';
	const IPN_CALLBACK                   = 'alma_ipn_callback';
	const CHECKOUT_NONCE                 = 'alma_checkout_nonce';
	const DEFAULT_FEE_PLAN               = 'general_3_0_0';
	const DEFAULT_CHECK_VARIATIONS_EVENT = 'check_variations';
	const AMOUNT_PLAN_KEY_REGEX          = '#^(min|max)_amount_general_[0-9]+_[0-9]+_[0-9]+$#';
	const PAYMENT_METHOD_PNX             = 'payment_method_pnx';
	const PAYMENT_METHOD_PAY_LATER       = 'payment_method_pay_later';
	const PAYMENT_METHOD_PNX_PLUS_4      = 'payment_method_pnx_plus_4';
	const NOTICE_NONCE_NAME              = 'wc_alma_notice_nonce';
	const ALMA_LOGO_PATH                 = 'images/alma_logo.svg';
	const ALMA_PATH_CHECKOUT_JS          = 'js/alma-checkout.js';

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
}
