/*
 * Alma Frontend In Page Implementation
 * This script initializes the Alma In Page payment on the frontend.
 * @see includes/Infrastructure/Helper/AssetsHelper.php
 */
(function ($) {
    $(function () {

        // ==================== PROTECTION AGAINST MULTIPLE EXECUTIONS ====================
        // If the script has already been initialized, do not re-execute it
        if (window.almaInPageInitialized) {
            return;
        }

        // Mark as initialized
        window.almaInPageInitialized = true;
        // ===============================================================================

        // ==================== TEST MODE - PRODUCTION CONDITIONS SIMULATION ====================
        // Simulates a slow SDK load (as in production with many JS files)
        const SDK_DELAY = 3000; // 3 seconds delay
        const TEST_MODE = false; // Test mode enabled

        if (TEST_MODE) {
            console.warn('⚠️ TEST MODE ENABLED: Alma SDK delayed by ' + SDK_DELAY + 'ms (production simulation)');

            if (typeof Alma !== 'undefined') {
                const realAlma = window.Alma;
                delete window.Alma; // Temporarily hide the SDK

                setTimeout(function () {
                    window.Alma = realAlma; // Restore after delay
                    console.log('✅ Alma SDK available (after simulated delay of ' + SDK_DELAY + 'ms)');
                }, SDK_DELAY);
            }
        }
        // ===================================================================================

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
        let isAlmaSDKReady = false;

        // Use a global variable to track overlay state across all script instances
        if (typeof window.almaOverlayAdded === 'undefined') {
            window.almaOverlayAdded = false;
        }

        // Track if payment has been started to prevent multiple calls
        if (typeof window.almaPaymentStarted === 'undefined') {
            window.almaPaymentStarted = false;
        }

        // Track if Alma was hidden due to SDK timeout
        if (typeof window.almaHiddenDueToTimeout === 'undefined') {
            window.almaHiddenDueToTimeout = false;
        }

        // Track if Alma methods should be kept hidden across DOM re-renders (updated_checkout)
        if (typeof window.almaMethodsShouldBeHidden === 'undefined') {
            window.almaMethodsShouldBeHidden = false;
        }

        // ==================== SDK POLLING SINGLETON ====================
        // A single polling loop shared across all callers.
        // This prevents multiple concurrent waitForAlmaSDK() instances from
        // triggering their callbacks simultaneously when the SDK finally loads.
        if (typeof window.almaSDKPoller === 'undefined') {
            window.almaSDKPoller = {
                running: false,       // Is the polling loop active?
                resolved: false,      // Has the SDK been detected at least once?
                callbacks: [],        // Success callbacks queued by callers
                errorCallbacks: [],   // Error callbacks queued by callers
                attempts: 0,
                maxAttempts: 100,     // 100 × 100ms = 10s timeout
                timeoutFired: false,

                /**
                 * Register a callback to run when the SDK is ready (or immediately if already ready).
                 * @param {Function} onReady
                 * @param {Function} onError
                 */
                onReady: function (onReady, onError) {
                    if (this.resolved) {
                        // SDK already available — run immediately
                        if (typeof onReady === 'function') {
                            onReady();
                        }
                        return;
                    }
                    if (typeof onReady === 'function') {
                        this.callbacks.push(onReady);
                    }
                    if (typeof onError === 'function') {
                        this.errorCallbacks.push(onError);
                    }
                    this.start();
                },

                /** Start the polling loop (idempotent). */
                start: function () {
                    if (this.running) {
                        return;
                    }
                    this.running = true;
                    this._tick();
                },

                _tick: function () {
                    var self = this;
                    self.attempts++;

                    if (typeof Alma !== 'undefined' && typeof Alma.InPage !== 'undefined') {
                        // SDK is ready
                        self.running = false;
                        self.resolved = true;
                        isAlmaSDKReady = true;

                        // Capture whether we were in a "hidden due to timeout" state
                        // before we start modifying any flags
                        var wasHiddenDueToTimeout = window.almaHiddenDueToTimeout;

                        // Restore Alma methods if they were hidden due to timeout.
                        // Use setTimeout(0) to defer until after any pending updated_checkout
                        // events already in the event loop have finished processing —
                        // otherwise they would immediately re-hide the methods.
                        if (wasHiddenDueToTimeout) {
                            window.almaHiddenDueToTimeout = false;
                            window.almaPaymentMethodBeforeHiding = null;

                            setTimeout(function () {
                                // Clear the flag only now, after pending DOM operations have settled
                                window.almaMethodsShouldBeHidden = false;

                                $('.woocommerce-notices-wrapper .alma-error-notice').remove();
                                $('.wc_payment_method[class*="payment_method_alma_"]').show();
                                console.log('[Alma] ✅ SDK loaded after delay – Alma methods restored (customer keeps their current selection)');
                            }, 0);
                        }

                        // Fire all queued success callbacks — consume the list once.
                        // If the SDK loaded after a timeout (Alma methods were hidden),
                        // do NOT run any callbacks: we only restore the visibility of Alma
                        // in the payment list. The customer keeps their current payment selection
                        // and the iframe / popup is never triggered automatically.
                        var cbs = wasHiddenDueToTimeout ? [] : self.callbacks.slice();
                        self.callbacks = [];
                        self.errorCallbacks = [];

                        if (wasHiddenDueToTimeout) {
                            console.log('[Alma] SDK ready after timeout — all stale callbacks discarded, no popup triggered');
                        }

                        cbs.forEach(function (cb) {
                            try {
                                cb();
                            } catch (e) {
                                console.error('[Alma] SDK-ready callback error:', e);
                            }
                        });
                        return;
                    }

                    // Timeout reached — fire error callbacks once
                    if (self.attempts >= self.maxAttempts && !self.timeoutFired) {
                        self.timeoutFired = true;
                        window.almaHiddenDueToTimeout = true;
                        window.almaMethodsShouldBeHidden = true;

                        var errMsg = '[Alma] SDK failed to load after ' + (self.maxAttempts * 100) + 'ms';
                        console.error(errMsg);

                        var isAlmaSelected = $('input[name="payment_method"][value^="alma_"]:checked').length > 0;
                        if (isAlmaSelected) {
                            displayErrorMessage(
                                'The Alma payment system could not be loaded. ' +
                                'Please refresh the page or choose another payment method.'
                            );
                        } else {
                            hideAlmaPaymentMethods();
                        }

                        var ecbs = self.errorCallbacks.slice();
                        self.errorCallbacks = [];
                        self.callbacks = [];
                        ecbs.forEach(function (cb) {
                            try {
                                cb(errMsg);
                            } catch (e) {
                            }
                        });
                        // Keep polling — SDK may still arrive later
                    }

                    // Continue polling
                    setTimeout(function () {
                        self._tick();
                    }, 100);
                }
            };
        }
        // ==============================================================

        const merchantId = alma_in_page_settings.merchant_id;
        const environment = alma_in_page_settings.environment.toUpperCase();

        const urlParams = new URLSearchParams(window.location.search);
        const isInPagePayment = urlParams.has('alma') && urlParams.get('alma') === 'inPage' && urlParams.has('pid');

        // Reset payment flag if we're landing on the page with inPage payment parameters
        // This ensures a fresh payment cycle
        if (isInPagePayment) {
            window.almaPaymentStarted = false;
            window.almaSDKPollingActive = false; // Reset polling flag for new page
            window.almaHiddenDueToTimeout = false; // Reset timeout flag
            console.log('[Init] Detected inPage payment URL, reset almaPaymentStarted flag');

            // Show loading overlay immediately when landing on inPage payment URL
            // This provides visual feedback during SDK loading and prevents user confusion
            addLoadingOverlay();
            console.log('[Init] Loading overlay displayed for inPage payment');
        }

        // Get all defined gateway names
        // Some of them may be undefined if the corresponding gateway is not available
        const gatewayVars = [
            typeof alma_woocommerce_gateway_credit_gateway !== "undefined" ? alma_woocommerce_gateway_credit_gateway : null,
            typeof alma_woocommerce_gateway_pay_later_gateway !== "undefined" ? alma_woocommerce_gateway_pay_later_gateway : null,
            typeof alma_woocommerce_gateway_pay_now_gateway !== "undefined" ? alma_woocommerce_gateway_pay_now_gateway : null,
            typeof alma_woocommerce_gateway_pnx_gateway !== "undefined" ? alma_woocommerce_gateway_pnx_gateway : null,
        ].filter(Boolean);

        // Associate gateway names with their types and selectors
        // e.g. { 'alma_credit_gateway': { type: 'credit', fieldsetSelector: '.alma_woocommerce_gateway_credit' }, ... }
        const almaMethods = gatewayVars.reduce((acc, gw) => {
            acc[gw.gateway_name] = {
                type: gw.type,
                fieldsetSelector: `.alma_woocommerce_gateway_${gw.type}`,
            };
            return acc;
        }, {});

        /**
         * Wait for Alma SDK to be loaded before initializing.
         * Delegates to the global singleton poller so only one polling loop
         * ever runs, regardless of how many times this is called.
         * @param {Function} callback  - Called when SDK is ready
         * @param {Function} onError   - Called if SDK fails to load within timeout
         */
        function waitForAlmaSDK(callback, onError) {
            window.almaSDKPoller.onReady(callback, onError);
        }

        // Show the Alma In-Page iframe if the selected gateway is an Alma gateway
        // Otherwise, remove the iframe
        function mountIframe() {
            // Do not mount while Alma methods are intentionally hidden (SDK timeout)
            if (window.almaMethodsShouldBeHidden) {
                console.log('[mountIframe] Skipping — Alma methods are currently hidden');
                return;
            }

            const selectedMethod = $('input[name="payment_method"]:checked').val();
            const almaPlanSelected = $('.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]:checked').val();

            console.log('[mountIframe] Called - selectedMethod:', selectedMethod, 'almaPlanSelected:', almaPlanSelected, 'totalAmount:', totalAmount);

            if (almaMethods[selectedMethod] && almaPlanSelected && totalAmount > 0) {
                // Check if Alma SDK is loaded
                if (!isAlmaSDKReady) {
                    console.log('[mountIframe] SDK not ready, waiting...');
                    waitForAlmaSDK(
                        function () {
                            console.log('[mountIframe] SDK ready, retrying mount');
                            mountIframe(); // Retry mounting after SDK is loaded
                        },
                        function (error) {
                            console.error('[Alma] Cannot mount iframe: ' + error);
                        }
                    );
                    return;
                }

                // Check if inPage is already initialized with the same configuration
                // If yes, don't recreate it to avoid breaking the payment flow
                if (inPage !== undefined) {
                    console.log('[mountIframe] ⚠️ InPage already initialized, skipping re-initialization');
                    return;
                }

                try {
                    console.log('[mountIframe] Initializing Alma InPage...');
                    const [installmentsCount, deferredDays, deferredMonths] = almaPlanSelected.match(/\d+/g).map(Number);
                    console.log('[mountIframe] Plan details - installments:', installmentsCount, 'deferredDays:', deferredDays, 'deferredMonths:', deferredMonths);

                    // Generate the selector based on the selected plan
                    // Format: #alma_{payment_method}_gateway_in_page_{plan_key}
                    const planSelector = '#alma_' + almaMethods[selectedMethod].type + '_gateway_in_page_' + almaPlanSelected;
                    console.log('[mountIframe] Using selector:', planSelector);

                    inPage = Alma.InPage.initialize({
                        merchantId: merchantId,
                        amountInCents: totalAmount,
                        installmentsCount: installmentsCount,
                        deferredDays: deferredDays,
                        deferredMonths: deferredMonths,
                        selector: planSelector,
                        environment: environment,
                    });

                    console.log('[mountIframe] ✅ InPage initialized successfully:', inPage);
                    console.log('[mountIframe] InPage methods available:', Object.keys(inPage || {}));
                } catch (error) {
                    console.error('[Alma] Error initializing InPage:', error);
                    console.error('[Alma] Error stack:', error.stack);
                    inPage = undefined;

                    // Check if an Alma payment method is currently selected
                    const isAlmaSelected = $('input[name="payment_method"][value^="alma_"]:checked').length > 0;

                    if (isAlmaSelected) {
                        // User has selected Alma: display error message
                        displayErrorMessage(
                            'Unable to initialize Alma payment. ' +
                            'Please try again or choose another payment method.'
                        );
                    } else {
                        // User has NOT selected Alma: hide silently without message
                        hideAlmaPaymentMethods();
                    }
                }
            } else {
                console.log('[mountIframe] Conditions not met - skipping mount');
            }
        }

        // Unmount the Alma In-Page iframe
        // This is to ensure that the iframe is removed when switching between Alma gateways or plans
        // If the iframe is not removed, it may cause issues with the payment process
        function unmountIframe() {
            if (inPage !== undefined) {
                try {
                    inPage.unmount();
                } catch (error) {
                    // Ignore errors if iframe is already removed or doesn't exist
                    // This can happen during checkout updates when the DOM is being refreshed
                }
                inPage = undefined;
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

                // Ensure the corresponding plan div is visible
                const planKey = firstPlan.val();
                if (planKey) {
                    $('.alma_woocommerce_gateway_checkout_plan').hide();
                    $('#alma-checkout-plan-' + planKey).show();
                }
            }
        }

        // Get the total amount from the order total element
        // The amount is in the format "12,34 €" or "$12.34"
        // We need to extract the numeric value and convert it to cents
        function getAmount() {
            try {
                const totalText = $('.order-total .woocommerce-Price-amount').text().trim();
                const amount = parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);

                if (isNaN(amount) || amount <= 0) {
                    console.warn('[Alma] Invalid amount detected:', totalText, '-> parsed as:', amount);
                    return 0;
                }

                return Math.round(amount); // Ensure it's an integer
            } catch (error) {
                console.error('[Alma] Error parsing amount:', error);
                return 0;
            }
        }


        // Remove alma=inPage&pid=PAYMENT_ID from URL without reloading the page
        // This is to prevent starting the payment again if the user close the modal and refresh the page
        function cleanInPageUrlParams() {
            const url = new URL(window.location.href);
            const params = url.searchParams;
            $('#alma-overlay').remove();
            window.almaOverlayAdded = false; // Reset flag when overlay is removed
            window.almaPaymentStarted = false; // Reset payment flag

            params.delete('alma');
            params.delete('pid');
            window.history.replaceState({}, document.title, url.pathname + '?' + params.toString());
        }

        /**
         * Hide Alma payment methods.
         * Sets a persistent flag so they stay hidden even after WooCommerce DOM re-renders
         * (updated_checkout recreates the payment list, which would otherwise undo a CSS hide).
         */
        function hideAlmaPaymentMethods() {
            // Set persistent flag FIRST — updated_checkout will honour it on re-render
            window.almaMethodsShouldBeHidden = true;

            // Uncheck Alma payment method if it's selected
            const $almaPaymentMethod = $('input[name="payment_method"][value^="alma_"]:checked');
            if ($almaPaymentMethod.length) {
                $almaPaymentMethod.prop('checked', false);

                // Hide all Alma payment boxes
                $('.payment_box[class*="payment_method_alma_"]').hide();

                // Select the first non-Alma payment method available
                const $firstOtherMethod = $('input[name="payment_method"]').not('[value^="alma_"]').first();
                if ($firstOtherMethod.length) {
                    $firstOtherMethod.prop('checked', true).trigger('change');
                }
            }

            // Hide all Alma payment methods from the list
            $('.wc_payment_method[class*="payment_method_alma_"]').hide();

            console.log('[Alma] Payment methods hidden (flag set to persist across DOM re-renders)');
        }


        /**
         * Display an error message to the user
         * Creates a WooCommerce-style error notice that is dismissible
         * @param {String} message - The error message to display
         * @param {String} errorType - The type of error (optional: 'warning', 'error', 'info')
         * @param {Boolean} hideAlmaMethods - Whether to hide Alma payment methods (default: true)
         */
        function displayErrorMessage(message, errorType = 'error', hideAlmaMethods = true) {
            // Check if Alma is currently selected before displaying error
            const isAlmaSelected = $('input[name="payment_method"][value^="alma_"]:checked').length > 0;

            if (!isAlmaSelected) {
                console.log('[Alma] Alma not selected, skipping error message display');
                // Just hide methods silently if requested
                if (hideAlmaMethods) {
                    hideAlmaPaymentMethods();
                }
                return;
            }

            // Remove any existing Alma error messages
            $('.woocommerce-notices-wrapper .alma-error-notice').remove();

            // Hide Alma payment methods if requested
            if (hideAlmaMethods) {
                hideAlmaPaymentMethods();
            }

            // Create the error notice HTML
            const noticeClass = errorType === 'warning' ? 'woocommerce-info' : 'woocommerce-error';
            const $errorNotice = $('<div>', {
                class: 'woocommerce-notices-wrapper',
                role: 'alert'
            });

            const $notice = $('<ul>', {
                class: noticeClass + ' alma-error-notice',
                role: 'alert'
            });

            const $listItem = $('<li>').html(message);
            $notice.append($listItem);
            $errorNotice.append($notice);

            // Insert the error notice at the top of the checkout form
            const $checkoutForm = $('form.checkout');
            if ($checkoutForm.length) {
                $checkoutForm.prepend($errorNotice);
            } else {
                // Fallback: insert after the first .woocommerce-notices-wrapper or at the top of the page
                const $noticesWrapper = $('.woocommerce-notices-wrapper').first();
                if ($noticesWrapper.length) {
                    $noticesWrapper.after($errorNotice);
                } else {
                    $('body').prepend($errorNotice);
                }
            }

            // Scroll to the error message
            $('html, body').animate({
                scrollTop: $errorNotice.offset().top - 100
            }, 500);

            // Log the error for debugging
            console.error('[Alma Error] Displayed to user:', message);
        }

        // Generate and add the loading overlay to the page
        // This overlay is removed when the inPage modal is closed
        function addLoadingOverlay() {
            // Check global flag first - most reliable check
            if (window.almaOverlayAdded) {
                return; // Already added, don't add again
            }

            // Double check in DOM as safety
            if ($('#alma-overlay').length > 0) {
                window.almaOverlayAdded = true;
                return; // Overlay already exists, don't create a new one
            }

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
                alt: 'Loading Alma',
                css: {
                    width: '100px',
                    height: '100px'
                }
            });

            $overlay.append($image);
            $('body').append($overlay);
            window.almaOverlayAdded = true;

            // Debug: Log all Alma-related elements in the DOM
            setTimeout(function () {
                console.log('[Alma Debug] DOM Analysis:');
                console.log('  - #alma-overlay count:', $('#alma-overlay').length);
                console.log('  - All divs with "alma" in id:', $('div[id*="alma"]').length);
                console.log('  - All iframes in body:', $('body iframe').length);
                console.log('  - Alma SDK iframes:', $('iframe[src*="almapay"], iframe[id*="alma"]').length);

                // List all Alma-related elements
                $('div[id*="alma"], iframe[id*="alma"], iframe[src*="almapay"]').each(function (index) {
                    console.log('  - Element ' + (index + 1) + ':', this.tagName, 'id:', this.id, 'src:', this.src || 'N/A');
                });
            }, 500); // Wait 500ms to let SDK finish loading
        }

        /**
         * Safely start the payment with the InPage instance
         * Handles errors and cleanup
         * @param {String} paymentId - The payment ID to start
         */
        function safeStartPayment(paymentId) {
            console.log('[safeStartPayment] Called with paymentId:', paymentId, 'almaPaymentStarted:', window.almaPaymentStarted);
            console.log('[safeStartPayment] inPage state:', inPage);
            console.log('[safeStartPayment] inPage type:', typeof inPage);

            // Check if payment already started
            if (window.almaPaymentStarted) {
                console.warn('[safeStartPayment] ⚠️ Payment already started, ignoring duplicate call');
                return;
            }

            if (inPage === undefined) {
                console.error('[Alma] Cannot start payment: InPage instance is not initialized');
                displayErrorMessage(
                    'Alma payment cannot be started. ' +
                    'Please refresh the page or choose another payment method.'
                );
                cleanInPageUrlParams();
                return;
            }

            if (!paymentId) {
                console.error('[Alma] Cannot start payment: Payment ID is missing');
                displayErrorMessage(
                    'Error initializing payment. ' +
                    'Please try again or contact support.'
                );
                cleanInPageUrlParams();
                return;
            }

            // Mark payment as started BEFORE adding overlay and starting payment
            window.almaPaymentStarted = true;
            console.log('[safeStartPayment] ✅ Starting payment, flag set to true');

            // Add overlay only when actually starting payment
            addLoadingOverlay();

            console.log('[safeStartPayment] About to call inPage.startPayment()');
            console.log('[safeStartPayment] inPage.startPayment exists?', typeof inPage.startPayment);

            try {
                const result = inPage.startPayment({
                    paymentId: paymentId,
                    onUserCloseModal: function () {
                        // Reset flag when modal is closed
                        window.almaPaymentStarted = false;
                        console.log('[safeStartPayment] Modal closed, flag reset to false');
                        cleanInPageUrlParams();
                    }
                });
                console.log('[safeStartPayment] startPayment() returned:', result);
            } catch (error) {
                console.error('[Alma] Error starting payment:', error);
                console.error('[Alma] Error stack:', error.stack);
                window.almaPaymentStarted = false; // Reset flag on error

                // Display user-friendly error message
                displayErrorMessage(
                    'An error occurred while starting the Alma payment. ' +
                    'Please try again or choose another payment method.'
                );

                cleanInPageUrlParams();
            }
        }


        // Woocommerce updates the checkout (e.g. when changing address or applying a coupon)*
        // We need to remount the iframe after the update
        // We also reset the inPage instance to ensure a fresh initialization with the updated amount
        $(document.body).on('updated_checkout', function () {
            totalAmount = getAmount();
            unmountIframe(); // Properly unmount before resetting

            // If Alma methods should stay hidden (SDK timed out), re-apply the hide
            // because WooCommerce just re-rendered the entire payment list
            if (window.almaMethodsShouldBeHidden) {
                $('.wc_payment_method[class*="payment_method_alma_"]').hide();
                console.log('[updated_checkout] Alma methods kept hidden (SDK not yet ready)');
                return; // Don't mount iframe or check plans while Alma is hidden
            }

            checkPlan();

            // Start payment for inPage if URL contains alma=inPage&pid=PAYMENT_ID
            // This is used to handle the case where the user is redirected back to the checkout page after place order
            if (isInPagePayment) {
                // Wait for SDK to be ready before mounting and starting payment
                waitForAlmaSDK(
                    function () {
                        mountIframe();

                        // Wait for inPage to be initialized by mountIframe
                        // Use a more robust check with multiple attempts
                        let checkAttempts = 0;
                        const maxCheckAttempts = 10;

                        const checkAndStartPayment = function () {
                            checkAttempts++;

                            if (inPage !== undefined) {
                                safeStartPayment(urlParams.get('pid'));
                            } else if (checkAttempts < maxCheckAttempts) {
                                setTimeout(checkAndStartPayment, 100);
                            } else {
                                console.error('[Alma] InPage instance not initialized after ' + (maxCheckAttempts * 100) + 'ms');
                                displayErrorMessage(
                                    'The Alma payment system could not initialize correctly. ' +
                                    'Please refresh the page or choose another payment method.'
                                );
                                cleanInPageUrlParams();
                            }
                        };

                        checkAndStartPayment();
                    },
                    function (error) {
                        console.error('[Alma] Cannot start payment: ' + error);
                        cleanInPageUrlParams();
                    }
                );
            } else {
                // Normal case: just mount the iframe
                mountIframe();
            }
        });

        // Change payment method
        $(document.body).on('payment_method_selected', function () {
            uncheckPlan();
            checkPlan();
        });

        // Change plan
        $(document).on('change', '.alma_woocommerce_gateway_fieldset input[name="alma_plan_key"]', function () {
            const selectedPlanKey = $(this).val();
            console.log('[change plan] Selected plan:', selectedPlanKey);

            // Hide all plan divs for this gateway
            $('.alma_woocommerce_gateway_checkout_plan').hide();

            // Show only the selected plan div
            $('#alma-checkout-plan-' + selectedPlanKey).show();

            // Unmount and remount iframe with new plan
            unmountIframe();
            mountIframe();
        });

        // Initialize: Wait for Alma SDK to be loaded, then setup the initial state
        waitForAlmaSDK(
            function () {
                totalAmount = getAmount();
                checkPlan();
                mountIframe();

                // Note: If this is an inPage payment redirect (URL contains ?alma=inPage&pid=XXX),
                // the payment will be started in the 'updated_checkout' event handler
                // which is triggered automatically after the page loads
                if (isInPagePayment) {
                    console.log('[Init] InPage payment detected, will start payment in updated_checkout event');
                }
            },
            function (error) {
                console.error('[Alma] Cannot initialize plugin: ' + error);
                if (isInPagePayment) {
                    cleanInPageUrlParams();
                }
            }
        );
    })
})(jQuery);
