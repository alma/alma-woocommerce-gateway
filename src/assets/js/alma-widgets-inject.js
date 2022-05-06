/**
 * Inject payment plan widget.
 *
 * @package Alma_WooCommerce_Gateway
 */
jQuery(document).ready(function() {

	// almaAdminInternationalization();

	// var almaAdminGeneralHelper = new AlmaAdminHelper();
	// var almaAdminFeePlan = new AlmaAdminFeePlan( almaAdminGeneralHelper );
	// almaAdminFeePlan.renderFeePlan();
	// almaAdminFeePlan.initiateAlmaSelectMenuBehaviour();
	// almaAdminFeePlan.listenFeePlanCheckboxStatus();
	// almaAdminFeePlan.checkInputsOnSubmitActionTriggered();
	// almaAdminGeneralHelper.toggleTechnicalConfigFields();

});

/**
 * Alma admin General Helpers.
 */
function AlmaWidgetHelper() {
	return {
		/**
		 * Check if widget is visible.
		 *
		 * @param elem
		 * @returns {boolean}
		 */
		isVisible: function( elem ) {
			return ! ! ( elem && ( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length ) );
		},
		/**
		 * Get amount Element.
		 *
		 * @returns {null|*}
		 */
		getAmountElement: function() {
			var paymentPlansContainerId = "#alma-payment-plans";
			var settings                = jQuery( paymentPlansContainerId ).data( 'settings' );
			var amountQuerySelector = settings.amountQuerySelector;
			if ( amountQuerySelector ) {
				return document.querySelector( amountQuerySelector );
			}
			return null;
		},
		/**
		 * Get PaymentPlansContainerId.
		 *
		 * @returns String
		 */
		getPaymentPlansContainerId: function() {
			return '#alma-payment-plans';
		},
		/**
		 * Get settings.
		 *
		 * @returns {null|*}
		 */
		getSettings: function() {
			var paymentPlansContainerId = "#alma-payment-plans";
			return jQuery( paymentPlansContainerId ).data( 'settings' );
		},
		/**
		 * AlmaInitWidget.
		 * 
		 * @returns Null
		 */
		AlmaInitWidget: function () {
			// Make sure settings are up-to-date after a potential cart_totals refresh.
			var settings = AlmaWidgetHelper().getSettings();
			if ( ! settings ) {
				return null;
			}

			if (settings.hasExcludedProducts) {
				return null;
			}

			var merchantId = settings.merchantId;
			var amount     = parseInt( settings.amount );

			var amountElement = AlmaWidgetHelper().getAmountElement();
			if (amountElement) {
				if (AlmaWidgetHelper().isVisible( amountElement )) {
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
					amount = 0
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
					container: AlmaWidgetHelper().getPaymentPlansContainerId(),
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
		},
	}
}


jQuery( document ).ready(function ($) {
	
	var settings = AlmaWidgetHelper().getSettings();
	if ( ! settings ) {
		return;
	}

	var jqueryUpdateEvent = settings.jqueryUpdateEvent;
	var firstRender       = settings.firstRender;

	if ( firstRender ) {
		AlmaWidgetHelper().AlmaInitWidget();
	}

	if ( jqueryUpdateEvent ) {
		$(document.body).on(
			jqueryUpdateEvent,
			function () {
				// WooCommerce animates the appearing of the product's price when necessary options have been selected,
				// or its disappearing when some choices are missing. We first try to find an ongoing animation to
				// update our widget *after* the animation has taken place, so that it uses up-to-date information/DOM
				// in AlmaInitWidget.
				var amountElement = AlmaWidgetHelper().getAmountElement()
				var timer = $.timers.find(
					function (t) {
						return t.elem === jQuery(amountElement).closest('.woocommerce-variation').get(0)
					}
				)

				if (timer) {
					window.setTimeout(window.AlmaInitWidget, timer.anim.duration)
				} else if (AlmaWidgetHelper().isVisible(amountElement) || ! settings.amountQuerySelector) {
					AlmaWidgetHelper().AlmaInitWidget()
				}
			}
		);
	}
});
