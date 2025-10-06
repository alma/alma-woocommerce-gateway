/*
 * Alma Frontend In Page Implementation
 * This script initializes the Alma In Page payment on the frontend.
 * @see includes/Infrastructure/Helper/AssetsHelper.php
 */
(function ($) {
    $(function () {

        /**
         * @typedef {Object} alma_in_page_settings
         * @typedef {Object} alma_woocommerce_gateway_credit_gateway
         * @typedef {Object} alma_woocommerce_gateway_pay_later_gateway
         * @typedef {Object} alma_woocommerce_gateway_pay_now_gateway
         * @typedef {Object} alma_woocommerce_gateway_pnx_gateway
         */

        // Check settings
        if (!alma_in_page_settings) {
            console.warn('Alma in page settings not found!');
            return;
        }

        let inPage;
        let totalAmount = 0;
        const merchantId = alma_in_page_settings.merchant_id;

        // Get all defined gateway names
        // Some of them may be undefined if the corresponding gateway is not available
        const gatewayVars = [
            typeof alma_woocommerce_gateway_credit_gateway !== "undefined" ? alma_woocommerce_gateway_credit_gateway : null,
            typeof alma_woocommerce_gateway_pay_later_gateway !== "undefined" ? alma_woocommerce_gateway_pay_later_gateway : null,
            typeof alma_woocommerce_gateway_pay_now_gateway !== "undefined" ? alma_woocommerce_gateway_pay_now_gateway : null,
            typeof alma_woocommerce_gateway_pnx_gateway !== "undefined" ? alma_woocommerce_gateway_pnx_gateway : null,
        ].filter(Boolean);
        const gatewayNames = gatewayVars.map(gw => gw.gateway_name);

        // Associate gateway names with their types and selectors
        // e.g. { 'alma_credit_gateway': { type: 'credit', inPageSelector: '#alma_credit_in_page' , fieldsetSelector: '.alma_woocommerce_gateway_credit' }, ... }
        const almaMethods = gatewayVars.reduce((acc, gw) => {
            acc[gw.gateway_name] = {
                type: gw.type,
                inPageSelector: `#${gw.gateway_name}_in_page`,
                fieldsetSelector: `.alma_woocommerce_gateway_${gw.type}`,
            };
            return acc;
        }, {});


        // Intercept the form submission to handle Alma In-Page payment
        //TODO CHANGE WITH LOAD PAGE EVENT
        $('form.checkout').on('checkout_place_order_success', function (e, result) {
            const selectedMethod = jQuery('input[name="payment_method"]:checked').val();
            if (gatewayNames.some(gw => gw.id === selectedMethod)) {
                inPage.startPayment({paymentId: result.paymentId});
                return false; // Prevent the order review section from refreshing
            }
            return result;
        });

        // Show the Alma In-Page iframe if the selected gateway is an Alma gateway
        // Otherwise, remove the iframe
        function mountIframe() {
            let selectedMethod = $('input[name="payment_method"]:checked').val();
            let almaPlanSelected = $('.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]:checked').val();
            if (almaMethods[selectedMethod] && almaPlanSelected && totalAmount > 0) {
                let [installmentsCount, deferredDays, deferredMonths] = almaPlanSelected.match(/\d+/g).map(Number);
                inPage = Alma.InPage.initialize({
                    merchantId: merchantId,
                    amountInCents: totalAmount,
                    installmentsCount: installmentsCount,
                    deferredDays: deferredDays,
                    deferredMonths: deferredMonths,
                    selector: almaMethods[selectedMethod].inPageSelector,
                    environment: 'TEST'
                });
            }
        }

        function unmountIframe() {
            if (inPage !== undefined) {
                inPage.unmount()
            }
        }

        function uncheckPlan() {
            $('.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]').prop('checked', false);
        }

        function checkPlan() {
            let selectedMethod = $('input[name="payment_method"]:checked').val();
            if (almaMethods[selectedMethod]) {
                // alma_woocommerce_gateway_pnx
                const firstPlan = $(`${almaMethods[selectedMethod].fieldsetSelector} input[name="alma_plan_key"]`).first();
                firstPlan.trigger('click');
            }
        }

        function getAmount() {
            const totalText = $('.order-total .woocommerce-Price-amount').text().trim();
            return parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);
        }

        // Woocommerce updates the checkout (e.g. when changing address or applying a coupon)
        $(document.body).on('updated_checkout', function () {
            totalAmount = getAmount()
            inPage = undefined; // Reset inPage instance after partial reload
            checkPlan();
            mountIframe();
        });

        // Change payment method
        $(document.body).on('payment_method_selected', function () {
            uncheckPlan();
            checkPlan();
        });

        // Change plan
        $(document).on('change', '.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]', function () {
            unmountIframe();
            mountIframe()
        });

    })
})(jQuery);