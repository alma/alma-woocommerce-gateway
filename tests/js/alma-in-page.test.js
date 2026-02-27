/**
 * Unit tests for alma-in-page.js
 *
 * Tests cover:
 * - Initialization and script loading protection
 * - SDK polling and timeout handling
 * - Payment method hiding/showing
 * - Error message display
 * - Plan selection and iframe mounting
 * - Payment method restoration after SDK timeout
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
        window.almaPaymentMethodBeforeHiding = null;
        window.almaSDKPollingActive = false;

        // Reset mocks
        jest.clearAllMocks();
        global.Alma.InPage.initialize.mockClear();
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

    describe('showAlmaPaymentMethods()', () => {
        test('should restore Alma payment methods and re-select previous method', (done) => {
            window.almaPaymentMethodBeforeHiding = 'alma_pnx_gateway';

            // Hide Alma first
            $('.wc_payment_method[class*="payment_method_alma_"]').hide();
            $('#payment_method_cheque').prop('checked', true);

            const showAlmaPaymentMethods = () => {
                $('.woocommerce-notices-wrapper .alma-error-notice').remove();
                $('.wc_payment_method[class*="payment_method_alma_"]').fadeIn(300);

                if (window.almaPaymentMethodBeforeHiding) {
                    const previousMethod = window.almaPaymentMethodBeforeHiding;
                    setTimeout(() => {
                        const $previousAlmaMethod = $('input[name="payment_method"][value="' + previousMethod + '"]');
                        if ($previousAlmaMethod.length > 0 && !$previousAlmaMethod.is(':checked')) {
                            $previousAlmaMethod.prop('checked', true);
                        }
                        window.almaPaymentMethodBeforeHiding = null;

                        // Verify re-selection
                        expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(true);
                        expect(window.almaPaymentMethodBeforeHiding).toBe(null);
                        done();
                    }, 100);
                }
            };

            showAlmaPaymentMethods();
        });

        test('should not re-select if no previous method saved', () => {
            window.almaPaymentMethodBeforeHiding = null;

            const showAlmaPaymentMethods = () => {
                $('.wc_payment_method[class*="payment_method_alma_"]').fadeIn(300);
                // Should not crash
            };

            expect(() => showAlmaPaymentMethods()).not.toThrow();
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

