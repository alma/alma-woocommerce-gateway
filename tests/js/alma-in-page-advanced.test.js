/**
 * Advanced tests for alma-in-page.js
 *
 * Tests covering:
 * - Asynchronous operations
 * - Timers and delays
 * - Event handling
 * - Complex scenarios
 */

describe('Alma In-Page - Advanced Scenarios', () => {

    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = `
			<div class="woocommerce-checkout">
				<form class="checkout">
					<ul class="wc_payment_methods">
						<li class="wc_payment_method payment_method_alma_pnx_gateway">
							<input type="radio" name="payment_method" value="alma_pnx_gateway" id="payment_method_alma_pnx_gateway">
							<div class="payment_box payment_method_alma_pnx_gateway">
								<div class="alma_woocommerce_gateway_fieldset">
									<input type="radio" name="alma_plan_key" value="general_2_0_0" id="general_2_0_0">
								</div>
								<div id="alma-checkout-plan-general_2_0_0" class="alma_woocommerce_gateway_checkout_plan" style="display: none;">
									<div id="alma_pnx_gateway_in_page_general_2_0_0"></div>
								</div>
							</div>
						</li>
                        <li class="wc_payment_method payment_method_cheque">
                            <input type="radio" name="payment_method" value="cheque" id="payment_method_cheque">
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

        jest.clearAllMocks();
    });

    describe('SDK Polling with Timeout', () => {
        beforeEach(() => {
            jest.useFakeTimers();
        });

        afterEach(() => {
            jest.useRealTimers();
        });

        test('should succeed when SDK loads within timeout', () => {
            const callback = jest.fn();
            const onError = jest.fn();
            let attempts = 0;
            const maxAttempts = 50;

            // Simulate SDK not ready initially
            delete global.Alma;

            const checkSDK = () => {
                attempts++;

                if (typeof Alma !== 'undefined' && typeof Alma.InPage !== 'undefined') {
                    callback();
                    return;
                }

                if (attempts < maxAttempts) {
                    setTimeout(checkSDK, 100);
                } else {
                    onError('SDK timeout');
                }
            };

            checkSDK();

            // Fast-forward 2 seconds (20 attempts)
            jest.advanceTimersByTime(2000);

            // Make SDK available
            global.Alma = {InPage: {initialize: jest.fn()}};

            // Continue polling
            jest.advanceTimersByTime(100);

            expect(callback).toHaveBeenCalled();
            expect(onError).not.toHaveBeenCalled();
        });

        test('should timeout when SDK never loads', () => {
            const callback = jest.fn();
            const onError = jest.fn();
            let attempts = 0;
            const maxAttempts = 50;

            // SDK never becomes available
            delete global.Alma;

            const checkSDK = () => {
                attempts++;

                if (typeof Alma !== 'undefined' && typeof Alma.InPage !== 'undefined') {
                    callback();
                    return;
                }

                if (attempts < maxAttempts) {
                    setTimeout(checkSDK, 100);
                } else {
                    onError('SDK timeout');
                }
            };

            checkSDK();

            // Fast-forward past timeout (5 seconds)
            jest.advanceTimersByTime(5000);

            expect(callback).not.toHaveBeenCalled();
            expect(onError).toHaveBeenCalledWith('SDK timeout');
        });

        test('should handle SDK loading exactly at timeout threshold', () => {
            const callback = jest.fn();
            let attempts = 0;
            const maxAttempts = 50;

            delete global.Alma;

            const checkSDK = () => {
                attempts++;

                if (typeof Alma !== 'undefined' && typeof Alma.InPage !== 'undefined') {
                    callback();
                    return;
                }

                if (attempts < maxAttempts) {
                    setTimeout(checkSDK, 100);
                }
            };

            checkSDK();

            // Advance to just before timeout (4.8 seconds = 48 attempts)
            jest.advanceTimersByTime(4800);
            expect(callback).not.toHaveBeenCalled();

            // Make SDK available
            global.Alma = {InPage: {initialize: jest.fn()}};

            // Advance to trigger the next check (100ms)
            jest.advanceTimersByTime(100);

            expect(callback).toHaveBeenCalled();
        });
    });

    describe('Payment method restoration with delay', () => {
        beforeEach(() => {
            jest.useFakeTimers();
        });

        afterEach(() => {
            jest.useRealTimers();
        });

        test('should wait 500ms before re-selecting Alma', () => {
            window.almaPaymentMethodBeforeHiding = 'alma_pnx_gateway';
            $('#payment_method_cheque').prop('checked', true);

            const showAlmaPaymentMethods = () => {
                $('.wc_payment_method[class*="payment_method_alma_"]').fadeIn(300);

                if (window.almaPaymentMethodBeforeHiding) {
                    const previousMethod = window.almaPaymentMethodBeforeHiding;

                    setTimeout(() => {
                        const $previousAlmaMethod = $('input[name="payment_method"][value="' + previousMethod + '"]');
                        if ($previousAlmaMethod.length > 0 && !$previousAlmaMethod.is(':checked')) {
                            $previousAlmaMethod.prop('checked', true);
                        }
                        window.almaPaymentMethodBeforeHiding = null;
                    }, 500);
                }
            };

            showAlmaPaymentMethods();

            // Before 500ms
            jest.advanceTimersByTime(400);
            expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(false);
            expect(window.almaPaymentMethodBeforeHiding).toBe('alma_pnx_gateway');

            // After 500ms
            jest.advanceTimersByTime(100);
            expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(true);
            expect(window.almaPaymentMethodBeforeHiding).toBe(null);
        });
    });

    describe('Complex integration scenarios', () => {
        test('should handle complete flow: timeout → hide → SDK loads → restore', () => {
            jest.useFakeTimers();

            // 1. User selects Alma
            $('#payment_method_alma_pnx_gateway').prop('checked', true);
            expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(true);

            // 2. SDK timeout occurs - hide Alma
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
                window.almaHiddenDueToTimeout = true;
            };

            hideAlmaPaymentMethods();

            expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(false);
            expect($('#payment_method_cheque').is(':checked')).toBe(true);
            expect(window.almaPaymentMethodBeforeHiding).toBe('alma_pnx_gateway');
            expect(window.almaHiddenDueToTimeout).toBe(true);

            // 3. SDK finally loads - restore Alma
            const showAlmaPaymentMethods = () => {
                if (window.almaHiddenDueToTimeout) {
                    if (window.almaPaymentMethodBeforeHiding) {
                        const previousMethod = window.almaPaymentMethodBeforeHiding;

                        setTimeout(() => {
                            const $previousAlmaMethod = $('input[name="payment_method"][value="' + previousMethod + '"]');
                            if ($previousAlmaMethod.length > 0) {
                                $previousAlmaMethod.prop('checked', true);
                            }
                            window.almaPaymentMethodBeforeHiding = null;
                            window.almaHiddenDueToTimeout = false;
                        }, 500);
                    }
                }
            };

            showAlmaPaymentMethods();

            // Wait for restoration
            jest.advanceTimersByTime(500);

            expect($('#payment_method_alma_pnx_gateway').is(':checked')).toBe(true);
            expect(window.almaPaymentMethodBeforeHiding).toBe(null);
            expect(window.almaHiddenDueToTimeout).toBe(false);

            jest.useRealTimers();
        });

        test('should handle plan change with iframe remount', () => {
            $('#payment_method_alma_pnx_gateway').prop('checked', true);
            $('#general_2_0_0').prop('checked', true);
            $('#alma-checkout-plan-general_2_0_0').show();

            // Simulate Alma SDK initialization
            const mockInPage = {
                unmount: jest.fn(),
                startPayment: jest.fn()
            };

            global.Alma.InPage.initialize.mockReturnValue(mockInPage);

            // Initialize with plan 2x
            const inPage1 = global.Alma.InPage.initialize({
                selector: '#alma_pnx_gateway_in_page_general_2_0_0',
                installmentsCount: 2
            });

            expect(global.Alma.InPage.initialize).toHaveBeenCalledWith(
                expect.objectContaining({installmentsCount: 2})
            );

            // Change to plan 3x
            $('#general_2_0_0').prop('checked', false);
            $('#general_3_0_0').prop('checked', true);
            $('#alma-checkout-plan-general_2_0_0').hide();
            $('#alma-checkout-plan-general_3_0_0').show();

            // Unmount old iframe
            inPage1.unmount();
            expect(mockInPage.unmount).toHaveBeenCalled();

            // Mount new iframe
            const inPage2 = global.Alma.InPage.initialize({
                selector: '#alma_pnx_gateway_in_page_general_3_0_0',
                installmentsCount: 3
            });

            expect(global.Alma.InPage.initialize).toHaveBeenCalledWith(
                expect.objectContaining({installmentsCount: 3})
            );
        });

        test('should not show error if Alma deselected before error occurs', () => {
            // User selects Alma
            $('#payment_method_alma_pnx_gateway').prop('checked', true);

            // User changes mind and selects another method
            $('#payment_method_alma_pnx_gateway').prop('checked', false);
            $('#payment_method_cheque').prop('checked', true);

            // Error occurs (SDK timeout)
            const displayErrorMessage = (message) => {
                const isAlmaSelected = $('input[name="payment_method"][value^="alma_"]:checked').length > 0;

                if (!isAlmaSelected) {
                    return; // Should not display
                }

                const $errorNotice = $('<div class="woocommerce-error alma-error-notice"></div>');
                $('form.checkout').prepend($errorNotice);
            };

            displayErrorMessage('SDK timeout error');

            // Error should NOT be displayed
            expect($('.alma-error-notice').length).toBe(0);
        });
    });

    describe('Edge cases and error handling', () => {
        test('should handle missing DOM elements gracefully', () => {
            // Remove order total
            $('.order-total').remove();

            const getAmount = () => {
                try {
                    const $orderTotal = $('.order-total .woocommerce-Price-amount');
                    if ($orderTotal.length === 0) {
                        return 0;
                    }
                    const totalText = $orderTotal.text().trim();
                    const amount = parseFloat(totalText.replace(/[^0-9.,]/g, '').replace(',', '.') * 100);
                    return isNaN(amount) ? 0 : Math.round(amount);
                } catch (e) {
                    return 0;
                }
            };

            expect(getAmount()).toBe(0);
        });

        test('should handle multiple rapid plan changes', () => {
            $('#payment_method_alma_pnx_gateway').prop('checked', true);

            const changes = ['general_2_0_0', 'general_3_0_0', 'general_2_0_0', 'general_3_0_0'];

            changes.forEach((planKey) => {
                $('.alma_woocommerce_gateway_checkout_plan').hide();
                $('#alma-checkout-plan-' + planKey).show();
                $('input[name="alma_plan_key"]').prop('checked', false);
                $('#' + planKey).prop('checked', true);
            });

            // Only last plan should be visible (display not 'none')
            expect($('#alma-checkout-plan-general_3_0_0').css('display')).not.toBe('none');
            expect($('#alma-checkout-plan-general_2_0_0').css('display')).toBe('none');
        });

        test('should prevent multiple overlays', () => {
            window.almaOverlayAdded = false;

            const addLoadingOverlay = () => {
                if (window.almaOverlayAdded) {
                    return; // Already added
                }

                window.almaOverlayAdded = true;
                const $overlay = $('<div id="alma-overlay">Loading...</div>');
                $('body').append($overlay);
            };

            addLoadingOverlay();
            expect($('#alma-overlay').length).toBe(1);

            // Try to add again
            addLoadingOverlay();
            expect($('#alma-overlay').length).toBe(1); // Still only one
        });

        test('should display overlay immediately on inPage payment URL', () => {
            // Simulate inPage payment URL parameters
            delete window.location;
            window.location = new URL('http://example.com/?page_id=7&alma=inPage&pid=payment_123');

            // Reset flags
            window.almaOverlayAdded = false;
            window.almaPaymentStarted = false;

            // Simulate the initialization code
            const urlParams = new URLSearchParams(window.location.search);
            const isInPagePayment = urlParams.has('alma') && urlParams.get('alma') === 'inPage' && urlParams.has('pid');

            const addLoadingOverlay = () => {
                if (window.almaOverlayAdded) {
                    return;
                }
                if ($('#alma-overlay').length > 0) {
                    window.almaOverlayAdded = true;
                    return;
                }
                const $overlay = $('<div>', {id: 'alma-overlay'});
                $('body').append($overlay);
                window.almaOverlayAdded = true;
            };

            // This code should execute when inPage payment is detected
            if (isInPagePayment) {
                window.almaPaymentStarted = false;
                addLoadingOverlay();
            }

            // Verify overlay was added immediately
            expect(isInPagePayment).toBe(true);
            expect($('#alma-overlay').length).toBe(1);
            expect(window.almaOverlayAdded).toBe(true);
            expect(window.almaPaymentStarted).toBe(false);
        });
    });
});

