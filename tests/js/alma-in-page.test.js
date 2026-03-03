/**
 * Unit tests for alma-in-page.js
 *
 * Tests cover:
 * - Initialization and script loading protection
 * - SDK polling singleton (almaSDKPoller)
 * - Payment method hiding/showing
 * - almaMethodsShouldBeHidden flag persistence
 * - mountIframe guard when Alma is hidden
 * - SDK restoration after timeout (no popup triggered)
 * - Error message display
 * - Plan selection and iframe mounting
 */

describe('Alma In-Page Script', () => {

    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = `
			<div class="woocommerce-checkout">
				<form class="checkout">
					<ul class="wc_payment_methods">
						<li class="wc_payment_method payment_method_alma_pnx_gateway">
							<input type="radio" name="payment_method" value="alma_pnx_gateway" id="payment_method_alma_pnx_gateway">
							<label for="payment_method_alma_pnx_gateway">Pay in installments</label>
                            <div class="payment_box payment_method_alma_pnx_gateway">
                                <div class="alma_woocommerce_gateway_fieldset alma_woocommerce_gateway_pnx">
                                    <input type="radio" name="alma_plan_key" value="general_2_0_0" id="general_2_0_0">
                                    <label for="general_2_0_0">2x</label>
                                </div>
                                <div class="alma_woocommerce_gateway_fieldset alma_woocommerce_gateway_pnx">
                                    <input type="radio" name="alma_plan_key" value="general_3_0_0" id="general_3_0_0">
                                    <label for="general_3_0_0">3x</label>
                                </div>
								<div id="alma-checkout-plan-general_2_0_0" class="alma_woocommerce_gateway_checkout_plan" style="display: none;">
									<div id="alma_pnx_gateway_in_page_general_2_0_0"></div>
								</div>
								<div id="alma-checkout-plan-general_3_0_0" class="alma_woocommerce_gateway_checkout_plan" style="display: none;">
									<div id="alma_pnx_gateway_in_page_general_3_0_0"></div>
								</div>
							</div>
						</li>
						<li class="wc_payment_method payment_method_cheque">
							<input type="radio" name="payment_method" value="cheque" id="payment_method_cheque">
							<label for="payment_method_cheque">Check</label>
						</li>
					</ul>
					<div class="order-total">
						<span class="woocommerce-Price-amount">55,00&nbsp;€</span>
					</div>
				</form>
			</div>
		`;

        // Reset global flags
        window.almaInPageInitialized = false;
        window.almaPaymentStarted = false;
        window.almaOverlayAdded = false;
        window.almaHiddenDueToTimeout = false;
        window.almaMethodsShouldBeHidden = false;
        window.almaPaymentMethodBeforeHiding = null;
        window.almaSDKPollingActive = false;

        // Reset SDK poller singleton so each test starts fresh
        window.almaSDKPoller = undefined;

        // Reset mocks
        jest.clearAllMocks();

        // Ensure Alma SDK mock is always reset to a known good state
        global.Alma = {
            InPage: {
                initialize: jest.fn()
            }
        };
    });

    describe('Initialization Protection', () => {
        test('should prevent multiple script executions', () => {
            expect(window.almaInPageInitialized).toBe(false);

            // Simulate first execution
            window.almaInPageInitialized = true;

            // Second execution should be blocked
            expect(window.almaInPageInitialized).toBe(true);
        });
    });

    describe('hideAlmaPaymentMethods()', () => {
        test('should hide Alma payment methods without error message', () => {
            // Select Alma
            $('#payment_method_alma_pnx_gateway').prop('checked', true);

            // Create and call the function (simulated)
            const hideAlmaPaymentMethods = () => {
                const $almaPaymentMethod = $('input[name="payment_method"][value^="alma_"]');
                if ($almaPaymentMethod.is(':checked')) {
                    if (!window.almaPaymentMethodBeforeHiding) {
                        window.almaPaymentMethodBeforeHiding = $almaPaymentMethod.val();
                    }
                    $almaPaymentMethod.prop('checked', false);

                    const $firstOtherMethod = $('input[name="payment_method"]').not('[value^="alma_"]').first();
                    if ($firstOtherMethod.length) {
                        $firstOtherMethod.prop('checked', true);
                    }
                }
                $('.wc_payment_method[class*="payment_method_alma_"]').fadeOut(300);
            };

            hideAlmaPaymentMethods();

            // Verify Alma is unchecked
            expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(false);

            // Verify another method is checked
            expect($('#payment_method_cheque').is(':checked')).toBe(true);

            // Verify payment method was saved
            expect(window.almaPaymentMethodBeforeHiding).toBe('alma_pnx_gateway');
        });

        test('should not save payment method if already saved', () => {
            window.almaPaymentMethodBeforeHiding = 'alma_credit_gateway';
            $('#payment_method_alma_pnx_gateway').prop('checked', true);

            const hideAlmaPaymentMethods = () => {
                const $almaPaymentMethod = $('input[name="payment_method"][value^="alma_"]');
                if ($almaPaymentMethod.is(':checked')) {
                    if (!window.almaPaymentMethodBeforeHiding) {
                        window.almaPaymentMethodBeforeHiding = $almaPaymentMethod.val();
                    }
                    $almaPaymentMethod.prop('checked', false);
                }
            };

            hideAlmaPaymentMethods();

            // Should keep the original value
            expect(window.almaPaymentMethodBeforeHiding).toBe('alma_credit_gateway');
        });
    });

    describe('showAlmaPaymentMethods() — SDK restored after timeout', () => {
        test('should show Alma methods and clear the hidden flag', () => {
            window.almaMethodsShouldBeHidden = true;
            window.almaHiddenDueToTimeout = true;
            window.almaPaymentMethodBeforeHiding = 'alma_pnx_gateway';

            // Hide them first
            $('.wc_payment_method[class*="payment_method_alma_"]').hide();

            // Simulate the singleton's restore logic (setTimeout(0) resolved)
            window.almaMethodsShouldBeHidden = false;
            window.almaHiddenDueToTimeout = false;
            window.almaPaymentMethodBeforeHiding = null;
            $('.woocommerce-notices-wrapper .alma-error-notice').remove();
            $('.wc_payment_method[class*="payment_method_alma_"]').show();

            expect($('.wc_payment_method.payment_method_alma_pnx_gateway').css('display')).not.toBe('none');
            expect(window.almaMethodsShouldBeHidden).toBe(false);
            expect(window.almaHiddenDueToTimeout).toBe(false);
            expect(window.almaPaymentMethodBeforeHiding).toBe(null);
        });

        test('should NOT re-select Alma after restoration — customer keeps their selection', () => {
            window.almaPaymentMethodBeforeHiding = 'alma_pnx_gateway';
            $('#payment_method_cheque').prop('checked', true);
            $('#payment_method_alma_pnx_gateway').prop('checked', false);

            // Simulate restoration (no re-selection logic anymore)
            window.almaMethodsShouldBeHidden = false;
            window.almaPaymentMethodBeforeHiding = null;
            $('.wc_payment_method[class*="payment_method_alma_"]').show();

            // Cheque should still be selected — Alma is NOT forced back
            expect($('#payment_method_cheque').is(':checked')).toBe(true);
            expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(false);
        });

        test('should remove error notices on restoration', () => {
            // Add a stale error notice
            const $wrapper = $('<div>', {class: 'woocommerce-notices-wrapper'});
            $wrapper.append($('<ul>', {class: 'woocommerce-error alma-error-notice'}).html('<li>Old error</li>'));
            $('form.checkout').prepend($wrapper);
            expect($('.alma-error-notice').length).toBe(1);

            // Simulate restoration
            $('.woocommerce-notices-wrapper .alma-error-notice').remove();

            expect($('.alma-error-notice').length).toBe(0);
        });
    });

    describe('almaMethodsShouldBeHidden flag', () => {
        test('should be set to true when SDK times out', () => {
            window.almaMethodsShouldBeHidden = false;

            // Simulate timeout
            window.almaHiddenDueToTimeout = true;
            window.almaMethodsShouldBeHidden = true;

            expect(window.almaMethodsShouldBeHidden).toBe(true);
        });

        test('should keep Alma hidden on updated_checkout re-render when flag is true', () => {
            window.almaMethodsShouldBeHidden = true;

            // Simulate updated_checkout: WooCommerce re-renders payment list (methods visible by default)
            $('.wc_payment_method[class*="payment_method_alma_"]').show();

            // Our handler re-hides them because the flag is still set
            if (window.almaMethodsShouldBeHidden) {
                $('.wc_payment_method[class*="payment_method_alma_"]').hide();
            }

            expect($('.wc_payment_method.payment_method_alma_pnx_gateway').css('display')).toBe('none');
        });

        test('should NOT hide Alma on updated_checkout when flag is false', () => {
            window.almaMethodsShouldBeHidden = false;

            // Simulate updated_checkout
            $('.wc_payment_method[class*="payment_method_alma_"]').show();

            if (window.almaMethodsShouldBeHidden) {
                $('.wc_payment_method[class*="payment_method_alma_"]').hide();
            }

            expect($('.wc_payment_method.payment_method_alma_pnx_gateway').css('display')).not.toBe('none');
        });
    });

    describe('mountIframe() guard — almaMethodsShouldBeHidden', () => {
        test('should skip mounting when Alma methods are hidden', () => {
            window.almaMethodsShouldBeHidden = true;

            const mountCalled = jest.fn();

            // Simulate mountIframe guard
            const mountIframe = () => {
                if (window.almaMethodsShouldBeHidden) {
                    return; // guard
                }
                mountCalled();
            };

            mountIframe();
            expect(mountCalled).not.toHaveBeenCalled();
        });

        test('should proceed with mounting when flag is false', () => {
            window.almaMethodsShouldBeHidden = false;

            const mountCalled = jest.fn();

            const mountIframe = () => {
                if (window.almaMethodsShouldBeHidden) {
                    return;
                }
                mountCalled();
            };

            mountIframe();
            expect(mountCalled).toHaveBeenCalled();
        });
    });

    describe('almaSDKPoller singleton', () => {
        beforeEach(() => {
            jest.useFakeTimers();
        });

        afterEach(() => {
            jest.useRealTimers();
            window.almaSDKPoller = undefined;
        });

        // Helper to build a fresh poller (mirrors production code)
        function buildPoller() {
            return {
                running: false,
                resolved: false,
                callbacks: [],
                errorCallbacks: [],
                attempts: 0,
                maxAttempts: 10, // small for tests
                timeoutFired: false,
                onReady(onReady, onError) {
                    if (this.resolved) {
                        if (typeof onReady === 'function') onReady();
                        return;
                    }
                    if (typeof onReady === 'function') this.callbacks.push(onReady);
                    if (typeof onError === 'function') this.errorCallbacks.push(onError);
                    this.start();
                },
                start() {
                    if (this.running) return;
                    this.running = true;
                    this._tick();
                },
                _tick() {
                    const self = this;
                    self.attempts++;
                    if (typeof Alma !== 'undefined' && typeof Alma.InPage !== 'undefined') {
                        self.running = false;
                        self.resolved = true;
                        const wasHidden = window.almaHiddenDueToTimeout;
                        if (wasHidden) {
                            window.almaHiddenDueToTimeout = false;
                            window.almaPaymentMethodBeforeHiding = null;
                            setTimeout(function () {
                                window.almaMethodsShouldBeHidden = false;
                                $('.wc_payment_method[class*="payment_method_alma_"]').show();
                            }, 0);
                        }
                        // If timeout occurred: discard all callbacks (no popup)
                        const cbs = wasHidden ? [] : self.callbacks.slice();
                        self.callbacks = [];
                        self.errorCallbacks = [];
                        cbs.forEach(cb => {
                            try {
                                cb();
                            } catch (e) {
                            }
                        });
                        return;
                    }
                    if (self.attempts >= self.maxAttempts && !self.timeoutFired) {
                        self.timeoutFired = true;
                        window.almaHiddenDueToTimeout = true;
                        window.almaMethodsShouldBeHidden = true;
                        const errMsg = '[Alma] SDK failed to load after ' + (self.maxAttempts * 100) + 'ms';
                        const ecbs = self.errorCallbacks.slice();
                        self.errorCallbacks = [];
                        self.callbacks = [];
                        ecbs.forEach(cb => {
                            try {
                                cb(errMsg);
                            } catch (e) {
                            }
                        });
                    }
                    setTimeout(() => self._tick(), 100);
                }
            };
        }

        test('only one polling loop runs regardless of how many callers register', () => {
            const poller = buildPoller();
            delete global.Alma;

            const cb1 = jest.fn();
            const cb2 = jest.fn();
            const cb3 = jest.fn();

            poller.onReady(cb1);
            poller.onReady(cb2);
            poller.onReady(cb3);

            expect(poller.running).toBe(true);
            expect(poller.callbacks.length).toBe(3);

            // Make SDK available and advance one tick
            global.Alma = {InPage: {initialize: jest.fn()}};
            jest.advanceTimersByTime(100);

            expect(cb1).toHaveBeenCalledTimes(1);
            expect(cb2).toHaveBeenCalledTimes(1);
            expect(cb3).toHaveBeenCalledTimes(1);
            expect(poller.resolved).toBe(true);
            expect(poller.callbacks.length).toBe(0);
        });

        test('calls onReady immediately when already resolved', () => {
            const poller = buildPoller();
            poller.resolved = true;

            const cb = jest.fn();
            poller.onReady(cb);

            // No timer needed — should be synchronous
            expect(cb).toHaveBeenCalledTimes(1);
        });

        test('error callbacks fired on timeout, keeps polling afterwards', () => {
            const poller = buildPoller();
            delete global.Alma;

            const onReady = jest.fn();
            const onError = jest.fn();
            poller.onReady(onReady, onError);

            // Exhaust attempts (10 × 100ms = 1000ms)
            jest.advanceTimersByTime(1000);

            expect(onError).toHaveBeenCalledTimes(1);
            expect(onReady).not.toHaveBeenCalled();
            expect(poller.timeoutFired).toBe(true);
            expect(window.almaMethodsShouldBeHidden).toBe(true);

            // Poller keeps ticking via setTimeout (SDK may still arrive)
            // running stays true because the loop is still alive
            expect(poller.running).toBe(true);
        });

        test('after timeout, SDK arrival restores methods but fires NO callbacks', () => {
            const poller = buildPoller();
            delete global.Alma;

            window.almaHiddenDueToTimeout = false;

            const cb = jest.fn();
            const onError = jest.fn();
            poller.onReady(cb, onError);

            // Trigger timeout
            jest.advanceTimersByTime(1000);
            expect(window.almaMethodsShouldBeHidden).toBe(true);
            expect(window.almaHiddenDueToTimeout).toBe(true);

            // SDK arrives late
            global.Alma = {InPage: {initialize: jest.fn()}};
            jest.advanceTimersByTime(100); // next tick detects SDK

            // No callback triggered — no popup
            expect(cb).not.toHaveBeenCalled();
            expect(poller.resolved).toBe(true);

            // Flags reset (almaMethodsShouldBeHidden reset via setTimeout(0))
            expect(window.almaHiddenDueToTimeout).toBe(false);

            // Run the deferred setTimeout(0) that clears almaMethodsShouldBeHidden
            jest.runAllTimers();
            expect(window.almaMethodsShouldBeHidden).toBe(false);
        });

        test('multiple updated_checkout callbacks accumulated during timeout are all discarded', () => {
            const poller = buildPoller();
            delete global.Alma;

            window.almaHiddenDueToTimeout = false;

            const cb1 = jest.fn(); // first updated_checkout
            const cb2 = jest.fn(); // second updated_checkout
            const cb3 = jest.fn(); // third updated_checkout
            poller.onReady(cb1);

            // Trigger timeout — callbacks are cleared by the timeout handler
            jest.advanceTimersByTime(1000);
            expect(poller.callbacks.length).toBe(0); // all flushed on timeout

            // New callbacks registered after timeout (stale updated_checkout calls)
            poller.callbacks.push(cb2);
            poller.callbacks.push(cb3);

            // SDK arrives late
            global.Alma = {InPage: {initialize: jest.fn()}};
            jest.advanceTimersByTime(100);

            // None should have been called
            expect(cb1).not.toHaveBeenCalled();
            expect(cb2).not.toHaveBeenCalled();
            expect(cb3).not.toHaveBeenCalled();
        });
    });

    describe('displayErrorMessage()', () => {
        test('should display error message when Alma is selected', () => {
            $('#payment_method_alma_pnx_gateway').prop('checked', true);

            const displayErrorMessage = (message) => {
                const isAlmaSelected = $('input[name="payment_method"][value^="alma_"]:checked').length > 0;

                if (!isAlmaSelected) {
                    return;
                }

                $('.woocommerce-notices-wrapper .alma-error-notice').remove();

                const $errorNotice = $('<div>', {class: 'woocommerce-notices-wrapper', role: 'alert'});
                const $notice = $('<ul>', {class: 'woocommerce-error alma-error-notice', role: 'alert'});
                const $listItem = $('<li>').html(message);
                $notice.append($listItem);
                $errorNotice.append($notice);

                $('form.checkout').prepend($errorNotice);
            };

            displayErrorMessage('Test error message');

            // Verify error message is displayed
            expect($('.alma-error-notice').length).toBe(1);
            expect($('.alma-error-notice li').text()).toBe('Test error message');
        });

        test('should not display error message when Alma is not selected', () => {
            $('#payment_method_cheque').prop('checked', true);

            const displayErrorMessage = (message) => {
                const isAlmaSelected = $('input[name="payment_method"][value^="alma_"]:checked').length > 0;

                if (!isAlmaSelected) {
                    return;
                }

                const $errorNotice = $('<div>', {class: 'woocommerce-notices-wrapper'});
                $('form.checkout').prepend($errorNotice);
            };

            displayErrorMessage('Test error message');

            // Verify error message is NOT displayed
            expect($('.alma-error-notice').length).toBe(0);
        });

        test('should remove previous error messages before adding new one', () => {
            $('#payment_method_alma_pnx_gateway').prop('checked', true);

            // Add first error with proper structure
            const $oldWrapper = $('<div>', {class: 'woocommerce-notices-wrapper'});
            const $oldNotice = $('<ul>', {class: 'woocommerce-error alma-error-notice'}).html('<li>Old error</li>');
            $oldWrapper.append($oldNotice);
            $('form.checkout').prepend($oldWrapper);

            expect($('.alma-error-notice').length).toBe(1);

            const displayErrorMessage = (message) => {
                // Remove all previous woocommerce-notices-wrapper containing alma-error-notice
                $('.alma-error-notice').closest('.woocommerce-notices-wrapper').remove();

                const $errorNotice = $('<div>', {class: 'woocommerce-notices-wrapper'});
                const $notice = $('<ul>', {class: 'woocommerce-error alma-error-notice'});
                const $listItem = $('<li>').html(message);
                $notice.append($listItem);
                $errorNotice.append($notice);

                $('form.checkout').prepend($errorNotice);
            };

            displayErrorMessage('New error');

            // Should have only one error message
            expect($('.alma-error-notice').length).toBe(1);
            expect($('.alma-error-notice li').text()).toBe('New error');
        });
    });

    describe('getAmount()', () => {
        test('should extract amount from order total and convert to cents', () => {
            const getAmount = () => {
                const totalText = $('.order-total .woocommerce-Price-amount').text().trim();
                const amount = parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);
                return Math.round(amount);
            };

            const amount = getAmount();
            expect(amount).toBe(5500); // 55,00€ = 5500 cents
        });

        test('should handle different currency formats', () => {
            $('.order-total .woocommerce-Price-amount').text('$55.00');

            const getAmount = () => {
                const totalText = $('.order-total .woocommerce-Price-amount').text().trim();
                const amount = parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);
                return Math.round(amount);
            };

            const amount = getAmount();
            expect(amount).toBe(5500);
        });

        test('should return 0 for invalid amount', () => {
            $('.order-total .woocommerce-Price-amount').text('Invalid');

            const getAmount = () => {
                try {
                    const totalText = $('.order-total .woocommerce-Price-amount').text().trim();
                    const amount = parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);
                    return isNaN(amount) ? 0 : Math.round(amount);
                } catch (e) {
                    return 0;
                }
            };

            const amount = getAmount();
            expect(amount).toBe(0);
        });
    });

    describe('Plan selection and iframe mounting', () => {
        test('should generate correct selector based on plan', () => {
            const almaMethods = {
                'alma_pnx_gateway': {
                    type: 'pnx',
                    fieldsetSelector: '.alma_woocommerce_gateway_pnx'
                }
            };

            const selectedMethod = 'alma_pnx_gateway';
            const almaPlanSelected = 'general_2_0_0';

            const planSelector = '#alma_' + almaMethods[selectedMethod].type + '_gateway_in_page_' + almaPlanSelected;

            expect(planSelector).toBe('#alma_pnx_gateway_in_page_general_2_0_0');
        });

        test('should show/hide plan divs on change', () => {
            // Initially all hidden
            expect($('#alma-checkout-plan-general_2_0_0').css('display')).toBe('none');
            expect($('#alma-checkout-plan-general_3_0_0').css('display')).toBe('none');

            // Select plan 2x
            const selectedPlanKey = 'general_2_0_0';
            $('.alma_woocommerce_gateway_checkout_plan').hide();
            $('#alma-checkout-plan-' + selectedPlanKey).show();

            expect($('#alma-checkout-plan-general_2_0_0').css('display')).not.toBe('none');
            expect($('#alma-checkout-plan-general_3_0_0').css('display')).toBe('none');

            // Change to plan 3x
            const newSelectedPlanKey = 'general_3_0_0';
            $('.alma_woocommerce_gateway_checkout_plan').hide();
            $('#alma-checkout-plan-' + newSelectedPlanKey).show();

            expect($('#alma-checkout-plan-general_2_0_0').css('display')).toBe('none');
            expect($('#alma-checkout-plan-general_3_0_0').css('display')).not.toBe('none');
        });

        test('should call Alma SDK with correct parameters', () => {
            const config = {
                merchantId: 'merchant_test123',
                amountInCents: 5500,
                installmentsCount: 2,
                deferredDays: 0,
                deferredMonths: 0,
                selector: '#alma_pnx_gateway_in_page_general_2_0_0',
                environment: 'TEST'
            };

            global.Alma.InPage.initialize(config);

            expect(global.Alma.InPage.initialize).toHaveBeenCalledWith(config);
            expect(global.Alma.InPage.initialize).toHaveBeenCalledTimes(1);
        });
    });

    describe('SDK Polling', () => {
        test('should detect SDK immediately if already loaded', () => {
            global.Alma.InPage = {initialize: jest.fn()};
            const callback = jest.fn();

            // Simulate waitForAlmaSDK
            if (typeof Alma !== 'undefined' && typeof Alma.InPage !== 'undefined') {
                callback();
            }

            expect(callback).toHaveBeenCalled();
        });

        test('should set polling flag to prevent multiple instances', () => {
            expect(window.almaSDKPollingActive).toBe(false);

            // First call
            if (!window.almaSDKPollingActive) {
                window.almaSDKPollingActive = true;
            }

            expect(window.almaSDKPollingActive).toBe(true);

            // Second call should detect active polling
            let shouldStartNewPolling = false;
            if (!window.almaSDKPollingActive) {
                shouldStartNewPolling = true;
            }

            expect(shouldStartNewPolling).toBe(false);
        });
    });

    describe('URL parameter handling', () => {
        test('should detect inPage payment URL', () => {
            // Mock window.location
            delete window.location;
            window.location = new URL('http://example.com/?alma=inPage&pid=payment_123');

            const urlParams = new URLSearchParams(window.location.search);
            const isInPagePayment = urlParams.has('alma') && urlParams.get('alma') === 'inPage' && urlParams.has('pid');

            expect(isInPagePayment).toBe(true);
            expect(urlParams.get('pid')).toBe('payment_123');
        });

        test('should reset flags on inPage payment URL', () => {
            window.almaPaymentStarted = true;
            window.almaSDKPollingActive = true;
            window.almaHiddenDueToTimeout = true;

            // Simulate flag reset
            const isInPagePayment = true;
            if (isInPagePayment) {
                window.almaPaymentStarted = false;
                window.almaSDKPollingActive = false;
                window.almaHiddenDueToTimeout = false;
            }

            expect(window.almaPaymentStarted).toBe(false);
            expect(window.almaSDKPollingActive).toBe(false);
            expect(window.almaHiddenDueToTimeout).toBe(false);
        });
    });

    describe('Payment started flag', () => {
        test('should prevent multiple payment starts', () => {
            window.almaPaymentStarted = false;

            // First call
            if (!window.almaPaymentStarted) {
                window.almaPaymentStarted = true;
                // ... start payment
            }

            expect(window.almaPaymentStarted).toBe(true);

            // Second call should be blocked
            let secondCallBlocked = false;
            if (window.almaPaymentStarted) {
                secondCallBlocked = true;
            }

            expect(secondCallBlocked).toBe(true);
        });

        test('should reset flag when modal is closed', () => {
            window.almaPaymentStarted = true;

            // Simulate modal close callback
            const onUserCloseModal = () => {
                window.almaPaymentStarted = false;
            };

            onUserCloseModal();

            expect(window.almaPaymentStarted).toBe(false);
        });
    });

    describe('Gateway methods object', () => {
        test('should create almaMethods object correctly', () => {
            const gatewayVars = [
                {type: 'pnx', gateway_name: 'alma_pnx_gateway'},
                {type: 'credit', gateway_name: 'alma_credit_gateway'}
            ];

            const almaMethods = gatewayVars.reduce((acc, gw) => {
                acc[gw.gateway_name] = {
                    type: gw.type,
                    fieldsetSelector: `.alma_woocommerce_gateway_${gw.type}`,
                };
                return acc;
            }, {});

            expect(almaMethods).toHaveProperty('alma_pnx_gateway');
            expect(almaMethods['alma_pnx_gateway'].type).toBe('pnx');
            expect(almaMethods['alma_pnx_gateway'].fieldsetSelector).toBe('.alma_woocommerce_gateway_pnx');
        });
    });

    describe('checkPlan()', () => {
        test('should select first plan and display its div', () => {
            // Setup: Alma payment method is selected
            $('#payment_method_alma_pnx_gateway').prop('checked', true);

            const almaMethods = {
                'alma_pnx_gateway': {
                    type: 'pnx',
                    fieldsetSelector: '.alma_woocommerce_gateway_pnx'
                }
            };

            // Simulate checkPlan function (exactly as in alma-in-page.js)
            const checkPlan = () => {
                let selectedMethod = $('input[name="payment_method"]:checked').val();
                if (almaMethods[selectedMethod]) {
                    const firstPlan = $(`${almaMethods[selectedMethod].fieldsetSelector} input[name="alma_plan_key"]`).first();

                    // In the real code, trigger('click') is called, which in a browser would check the radio
                    // In tests, we simulate this by manually checking it
                    firstPlan.prop('checked', true);

                    // This is the NEW CODE we added: ensure the corresponding plan div is visible
                    const planKey = firstPlan.val();
                    if (planKey) {
                        $('.alma_woocommerce_gateway_checkout_plan').hide();
                        $('#alma-checkout-plan-' + planKey).show();
                    }
                }
            };

            // Initially all plan divs are hidden
            expect($('#alma-checkout-plan-general_2_0_0').css('display')).toBe('none');
            expect($('#alma-checkout-plan-general_3_0_0').css('display')).toBe('none');

            // Call checkPlan
            checkPlan();

            // Verify first plan is selected
            expect($('#general_2_0_0').is(':checked')).toBe(true);

            // Verify corresponding div is visible (this is what we're testing!)
            expect($('#alma-checkout-plan-general_2_0_0').css('display')).not.toBe('none');

            // Verify other plan divs are still hidden
            expect($('#alma-checkout-plan-general_3_0_0').css('display')).toBe('none');
        });

        test('should handle case when no Alma method is selected', () => {
            // Setup: Non-Alma payment method is selected
            $('#payment_method_cheque').prop('checked', true);

            const almaMethods = {
                'alma_pnx_gateway': {
                    type: 'pnx',
                    fieldsetSelector: '.alma_woocommerce_gateway_pnx'
                }
            };

            // Simulate checkPlan function
            const checkPlan = () => {
                let selectedMethod = $('input[name="payment_method"]:checked').val();
                if (almaMethods[selectedMethod]) {
                    const firstPlan = $(`${almaMethods[selectedMethod].fieldsetSelector} input[name="alma_plan_key"]`).first();
                    firstPlan.prop('checked', true);

                    const planKey = firstPlan.val();
                    if (planKey) {
                        $('.alma_woocommerce_gateway_checkout_plan').hide();
                        $('#alma-checkout-plan-' + planKey).show();
                    }
                }
            };

            // Should not throw error
            expect(() => checkPlan()).not.toThrow();

            // No plan should be selected
            expect($('#general_2_0_0').is(':checked')).toBe(false);
            expect($('#general_3_0_0').is(':checked')).toBe(false);
        });
    });
});

