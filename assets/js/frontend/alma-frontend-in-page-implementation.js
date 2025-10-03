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
        const merchantId = alma_in_page_settings.merchant_id;

        // Get all defined gateway types
        // Some of them may be undefined if the corresponding gateway is not available
        const gatewayTypes = [
            typeof alma_woocommerce_gateway_credit_gateway !== "undefined" ? alma_woocommerce_gateway_credit_gateway.type : null,
            typeof alma_woocommerce_gateway_pay_later_gateway !== "undefined" ? alma_woocommerce_gateway_pay_later_gateway.type : null,
            typeof alma_woocommerce_gateway_pay_now_gateway !== "undefined" ? alma_woocommerce_gateway_pay_now_gateway.type : null,
            typeof alma_woocommerce_gateway_pnx_gateway !== "undefined" ? alma_woocommerce_gateway_pnx_gateway.type : null,
        ].filter(type => type != null);

        let almaPlanSelected = $('#alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]:checked').val();
        let [installmentsCount, deferredDays, deferredMonths] = almaPlanSelected.match(/\d+/g).map(Number);

        // Intercept the form submission to handle Alma In-Page payment
        $('form.checkout').on('checkout_place_order_success', function (e, result) {
            const selectedMethod = jQuery('input[name="payment_method"]:checked').val();
            if (gatewayTypes.some(gw => gw.id === selectedMethod)) {
                console.log('Success! Preventing order review refresh...');
                console.log(result)
                inPage.startPayment({paymentId: result.paymentId});
                return false; // Prevent the order review section from refreshing
            }
            return result;
        });

        // Show the Alma In-Page iframe if the selected gateway is an Alma gateway
        // Otherwise, remove the iframe
        function showIframeForSelectedGateway() {
            let selectedMethod = $('input[name="payment_method"]:checked').val();
            let almaPlanSelected = $('#alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]:checked').val();

            console.log('totalAmount', totalAmount);

            if (gatewayTypes.includes(selectedMethod) && almaPlanSelected) {
                let [installmentsCount, deferredDays, deferredMonths] = almaPlanSelected.match(/\d+/g).map(Number);
                console.log('Initializing Alma In-Page for ' + selectedMethod);
                inPage = Alma.InPage.initialize({
                    merchantId: merchantId,
                    amountInCents: totalAmount,
                    installmentsCount: installmentsCount,
                    deferredDays: deferredDays,
                    deferredMonths: deferredMonths,
                    selector: "#alma-in-page-pnx",
                    environment: 'TEST'
                });
            } else {
                if (inPage !== undefined) {
                    inPage = null
                }
            }
        }

        function getAmount() {
            const totalText = $('.order-total .woocommerce-Price-amount').text().trim();
            return parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);
        }

        // Get the amount
        $(document.body).on('updated_checkout', function () {
            totalAmount = getAmount()
        });

        // Initial call
        let totalAmount = getAmount();
        showIframeForSelectedGateway();

        // Woocommerce updates the checkout (e.g. when changing address or applying a coupon)
        $(document.body).on('updated_checkout', function () {
            showIframeForSelectedGateway();
        });

        // Change payment method
        $(document.body).on('payment_method_selected', function () {
            showIframeForSelectedGateway();
        });

        // Change plan
        $(document).on('change', '#alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]', showIframeForSelectedGateway);

    })
})(jQuery);