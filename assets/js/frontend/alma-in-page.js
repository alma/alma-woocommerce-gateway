/*
 * Alma Frontend In Page Implementation
 * This script initializes the Alma In Page payment on the frontend.
 * @see includes/Infrastructure/Helper/AssetsHelper.php
 */
(function ($) {
    $(function () {

        // Check if it's an inPage payment on initial load
        const initialUrlParams = new URLSearchParams(window.location.search);
        const isInPagePayment = initialUrlParams.has('alma') && initialUrlParams.get('alma') === 'inPage' && initialUrlParams.has('pid') && initialUrlParams.has('planKey');

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
                fieldsetSelector: `.alma_woocommerce_gateway_${gw.type}`,
            };
            return acc;
        }, {});

        // Show the Alma In-Page iframe if the selected gateway is an Alma gateway
        // Otherwise, remove the iframe
        function mountIframe() {

            let installmentsCount, deferredDays, deferredMonths, almaPlanSelected;

            // Get current URL parameters (dynamically, not cached)
            const currentUrlParams = new URLSearchParams(window.location.search);

            // Get parameters from URL if available (when redirected back to checkout with payment)
            if (currentUrlParams.has('planKey')) {
                almaPlanSelected = currentUrlParams.get('planKey');
            } else {
                // Fallback to DOM when no URL parameters (normal selection)
                almaPlanSelected = $('.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]:checked').val();
                if (!almaPlanSelected) {
                    return;
                }
            }

            // Generate the selector based on the selected plan
            // Format: #alma_{payment_method}_gateway_in_page_{plan_key}
            const selectedMethod = $('input[name="payment_method"]:checked').val();
            const planSelector = '#alma_' + almaMethods[selectedMethod].type + '_gateway_in_page_' + almaPlanSelected;
            [installmentsCount, deferredDays, deferredMonths] = almaPlanSelected.match(/\d+/g).map(Number);

            // Initialize the Alma In-Page iframe
            if (almaMethods[selectedMethod] && totalAmount > 0) {
                inPage = Alma.InPage.initialize({
                    merchantId: merchantId,
                    amountInCents: totalAmount,
                    installmentsCount: installmentsCount,
                    deferredDays: deferredDays,
                    deferredMonths: deferredMonths,
                    selector: planSelector,
                    environment: environment,
                    locale: alma_in_page_settings.language,
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

        // Check the selected plan of the selected Alma gateway
        // If no plan is selected, the first plan will be selected by default
        // If a plan is selected, the iframe will be displayed with the corresponding selector
        function checkPlan() {
            let selectedMethod = $('input[name="payment_method"]:checked').val();
            if (almaMethods[selectedMethod]) {
                // Check if there's a planKey in URL (when redirected back from payment)
                const currentUrlParams = new URLSearchParams(window.location.search);
                const planKeyFromUrl = currentUrlParams.get('planKey');

                let planToSelect;
                if (planKeyFromUrl) {
                    // Select the plan specified in URL
                    planToSelect = $(`${almaMethods[selectedMethod].fieldsetSelector} input[name="alma_plan_key"][value="${planKeyFromUrl}"]`);
                } else {
                    // Select the first plan by default
                    planToSelect = $(`${almaMethods[selectedMethod].fieldsetSelector} input[name="alma_plan_key"]`).first();
                }

                if (planToSelect.length > 0) {
                    planToSelect.trigger('click');
                }
            }
        }

        // Get the total amount from the order total element
        // The amount is in the format "12,34 €" or "$12.34"
        // We need to extract the numeric value and convert it to cents
        function getAmount() {
            const totalText = $('.order-total .woocommerce-Price-amount').first().text().trim();

            const number_decimals = parseInt(alma_in_page_settings.number_decimals);

            let cleanedText = totalText.replace(/[^0-9]/g, '');
            return parseInt(cleanedText) * Math.pow(10, 2 - number_decimals);
        }


        // Remove alma=inPage&pid=PAYMENT_ID from URL without reloading the page
        // This is to prevent starting the payment again if the user close the modal and refresh the page
        function cleanInPageUrlParams() {
            const url = new URL(window.location.href);
            const params = url.searchParams;
            $('#alma-overlay').remove();

            params.delete('alma');
            params.delete('pid');
            params.delete('planKey');
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
                    paymentId: initialUrlParams.get('pid'),
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
