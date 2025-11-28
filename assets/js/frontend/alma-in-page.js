/*
 * Alma Frontend In Page Implementation
 * This script initializes the Alma In Page payment on the frontend.
 * @see includes/Infrastructure/Helper/AssetsHelper.php
 */
(function ($) {
    $(function () {

        const urlParams = new URLSearchParams(window.location.search);
        const isInPagePayment = urlParams.has('alma') && urlParams.get('alma') === 'inPage' && urlParams.has('pid');

        // Add overlay if it's an inPage payment to prevent user interactions while the payment modal is loading
        if (isInPagePayment) {
            addLoadingOverlay();
        }
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
        const environment = alma_in_page_settings.environment.toUpperCase();

        // Get all defined gateway names
        // Some of them may be undefined if the corresponding gateway is not available
        const gatewayVars = [
            typeof alma_woocommerce_gateway_credit_gateway !== "undefined" ? alma_woocommerce_gateway_credit_gateway : null,
            typeof alma_woocommerce_gateway_pay_later_gateway !== "undefined" ? alma_woocommerce_gateway_pay_later_gateway : null,
            typeof alma_woocommerce_gateway_pay_now_gateway !== "undefined" ? alma_woocommerce_gateway_pay_now_gateway : null,
            typeof alma_woocommerce_gateway_pnx_gateway !== "undefined" ? alma_woocommerce_gateway_pnx_gateway : null,
        ].filter(Boolean);

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

        // Show the Alma In-Page iframe if the selected gateway is an Alma gateway
        // Otherwise, remove the iframe
        function mountIframe() {
            const selectedMethod = $('input[name="payment_method"]:checked').val();
            const almaPlanSelected = $('.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]:checked').val();
            if (almaMethods[selectedMethod] && almaPlanSelected && totalAmount > 0) {
                const [installmentsCount, deferredDays, deferredMonths] = almaPlanSelected.match(/\d+/g).map(Number);
                inPage = Alma.InPage.initialize({
                    merchantId: merchantId,
                    amountInCents: totalAmount,
                    installmentsCount: installmentsCount,
                    deferredDays: deferredDays,
                    deferredMonths: deferredMonths,
                    selector: almaMethods[selectedMethod].inPageSelector,
                    environment: environment,
                });
            }
        }

        // Unmount the Alma In-Page iframe
        // This is to ensure that the iframe is removed when switching between Alma gateways or plans
        // If the iframe is not removed, it may cause issues with the payment process
        function unmountIframe() {
            if (inPage !== undefined) {
                inPage.unmount()
            }
        }

        // Uncheck all plans when switching between Alma gateways
        // This is to ensure that only one plan is selected at a time
        // If multiple plans are selected, the iframe will not be displayed
        function uncheckPlan() {
            $('.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]').prop('checked', false);
        }

        // Select the first plan of the selected Alma gateway
        // This is to ensure that a plan is always selected when switching between Alma gateways
        // If no plan is selected, the iframe will not be displayed
        function checkPlan() {
            let selectedMethod = $('input[name="payment_method"]:checked').val();
            if (almaMethods[selectedMethod]) {
                const firstPlan = $(`${almaMethods[selectedMethod].fieldsetSelector} input[name="alma_plan_key"]`).first();
                firstPlan.trigger('click');
            }
        }

        // Get the total amount from the order total element
        // The amount is in the format "12,34 €" or "$12.34"
        // We need to extract the numeric value and convert it to cents
        function getAmount() {
            const totalText = $('.order-total .woocommerce-Price-amount').text().trim();
            return parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);
        }


        // Remove alma=inPage&pid=PAYMENT_ID from URL without reloading the page
        // This is to prevent starting the payment again if the user close the modal and refresh the page
        function cleanInPageUrlParams() {
            const url = new URL(window.location.href);
            const params = url.searchParams;
            $('#alma-overlay').remove();

            params.delete('alma');
            params.delete('pid');
            window.history.replaceState({}, document.title, url.pathname + '?' + params.toString());
        }

        // Generate and add the loading overlay to the page
        // This overlay is removed when the inPage modal is closed
        function addLoadingOverlay() {
            const $overlay = $('<div>', {
                id: 'alma-overlay',
                css: {
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    width: '100%',
                    height: '100%',
                    backgroundColor: 'rgba(0,0,0,0.5)',
                    zIndex: 9999,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                }
            });
            const $image = $('<img>', {
                src: 'https://cdn.almapay.com/img/animated-logo-a.svg',
                alt: 'Chargement Alma',
                css: {
                    width: '100px',
                    height: '100px'
                }
            });

            $overlay.append($image);
            $('body').append($overlay);
        }


        // Woocommerce updates the checkout (e.g. when changing address or applying a coupon)*
        // We need to remount the iframe after the update
        // We also reset the inPage instance to ensure a fresh initialization with the updated amount
        $(document.body).on('updated_checkout', function () {
            totalAmount = getAmount()
            inPage = undefined; // Reset inPage instance after partial reload
            checkPlan();
            mountIframe();

            // Start payment for inPage if URL contains alma=inPage&pid=PAYMENT_ID
            // This is used to handle the case where the user is redirected back to the checkout page after place order
            if (isInPagePayment) {
                inPage.startPayment({
                    paymentId: urlParams.get('pid'),
                    onUserCloseModal: cleanInPageUrlParams
                });
            }
        });

        // Change payment method
        $(document.body).on('payment_method_selected', function () {
            uncheckPlan();
            checkPlan();
        });

        // Change plan
        $(document).on('change', '.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]', function () {
            unmountIframe();
            mountIframe();
        });
    })
})(jQuery);
