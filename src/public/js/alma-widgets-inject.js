/**
 * Inject payment plan widget.
 *
 * @package Alma_Gateway_For_Woocommerce
 */

jQuery( document ).ready(
	function ( $ ) {

		var almaWidgetHelper = new AlmaWidgetHelper();
		var settings         = almaWidgetHelper.getSettings();
		if ( ! settings ) {
			return;
		}

		var jqueryUpdateEvent = settings.jqueryUpdateEvent;
		var firstRender       = settings.firstRender;

		if ( firstRender ) {
			almaWidgetHelper.init();
		}

		if ( jqueryUpdateEvent ) {
			$( document.body ).on(
				jqueryUpdateEvent,
				function () {
					// WooCommerce animates the appearing of the product's price when necessary options have been selected,
					// or its disappearing when some choices are missing. We first try to find an ongoing animation to
					// update our widget *after* the animation has taken place, so that it uses up-to-date information/DOM
					// in init.
					var amountElement = almaWidgetHelper.getAmountElement( settings )
					var timer         = $.timers.find(
						function ( t ) {
							return t.elem === jQuery( amountElement ).closest( '.woocommerce-variation' ).get( 0 );
						}
					)

					if (timer) {
						window.setTimeout( almaWidgetHelper.init, timer.anim.duration )
					} else if ( almaWidgetHelper.isVisible( amountElement ) || ! settings.amountQuerySelector ) {
						almaWidgetHelper.init();
					}
				}
			);
		}
	}
);

/**
 * Alma admin General Helpers.
 */
function AlmaWidgetHelper() {

	/**
	 * Check if widget is visible.
	 *
	 * @param elem
	 * @returns {boolean}
	 */
	var isVisible = function( elem ) {
		return ! ! ( elem && ( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length ) );
	}

	/**
	 * Get amount Element.
	 *
	 * @param settings
	 *
	 * @returns {null|*}
	 */
	function getAmountElement( settings ) {
		var amountQuerySelector          = settings.amountQuerySelector;
		var amountSalePriceQuerySelector = settings.amountSalePriceQuerySelector;
		if ( document.querySelector( amountSalePriceQuerySelector ) ) {
			amountQuerySelector = amountSalePriceQuerySelector;
		}
		if ( amountQuerySelector ) {
			return document.querySelector( amountQuerySelector );
		}

		return null;
	}

	/**
	 * Get PaymentPlansContainerId.
	 *
	 * @returns String
	 */
	var getPaymentPlansContainerId = function() {
		return '#alma-payment-plans';
	}

	/**
	 * Get settings.
	 *
	 * @returns {null|*}
	 */
	var getSettings = function() {
		var paymentPlansContainerId = getPaymentPlansContainerId();
		return jQuery( paymentPlansContainerId ).data( 'settings' );
	}

	/**
	 * Inits the widget.
	 *
	 * @return void
	 */
	var init = function () {
		// Make sure settings are up-to-date after a potential cart_totals refresh.
		var settings = getSettings();
		if ( ! settings || settings.hasExcludedProducts ) {
			return;
		}

		var merchantId = settings.merchantId;
		var amount     = parseInt( settings.amount );

		var amountElement = getAmountElement( settings );
		if (amountElement) {
			if (isVisible( amountElement )) {
				var child = amountElement.firstChild;
				while (child) {
					if (child.nodeType === ( Node.TEXT_NODE || 3 )) {
						var strAmount = child.data
							.replace( settings.thousandSeparator, '' )
							.replace( settings.decimalSeparator, '.' )
							.replace( /[^\d.]/g, '' )

						amount = Alma.Utils.priceToCents( parseFloat( strAmount ) );
						break;
					}
					child = child.nextSibling;
				}
			} else {
				amount = parseInt( settings.amount );
			}
		}

		var almaApiMode = Alma.ApiMode.TEST;
		if (settings.apiMode === 'live') {
			almaApiMode = Alma.ApiMode.LIVE;
		}
		var widgets = Alma.Widgets.initialize( merchantId, almaApiMode )
		widgets.add(
			Alma.Widgets.PaymentPlans,
			{
				container: getPaymentPlansContainerId(),
				purchaseAmount: amount,
				locale: settings.locale, // [optional, default: en]
				hideIfNotEligible: false,
				plans: settings.enabledPlans.map(
					function ( plan ) {
						return {
							installmentsCount: plan.installments_count,
							minAmount: plan.min_amount,
							maxAmount: plan.max_amount,
							deferredDays: plan.deferred_days,
							deferredMonths: plan.deferred_months
						}
					}
				)
			}
		)
	}

	return {
		isVisible: isVisible,
		getAmountElement: getAmountElement,
		getPaymentPlansContainerId: getPaymentPlansContainerId,
		getSettings: getSettings,
		init: init
	}
}
