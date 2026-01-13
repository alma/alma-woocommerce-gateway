import {dispatch, useSelect} from '@wordpress/data';
import {useCallback, useEffect, useRef, useState} from '@wordpress/element';
import {AlmaBlock} from "./alma-block-component.tsx";

export const DisplayAlmaInPageBlock = (props) => {
    const {
        eventRegistration,
        emitResponse,
        gateway,
        storeKey,
    } = props;

    const {onPaymentSetup, onCheckoutSuccess, onCheckoutFail} = eventRegistration;

    // Get the Checkout and Cart Store Keys
    const {CHECKOUT_STORE_KEY, CART_STORE_KEY} = window.wc.wcBlocksData;

    // Get cart total from Redux store
    const {cartTotal} = useSelect((select) => ({
        cartTotal: select(CART_STORE_KEY).getCartTotals().total_price,
    }), []);

    // Get Alma settings and gateway settings from the store
    const {almaSettings, gatewaySettings, isLoading} = useSelect(
        (select) => ({
            almaSettings: select(storeKey).getAlmaSettings(),
            gatewaySettings: select(storeKey).getGatewaySettings(gateway),
            isLoading: select(storeKey).isLoading()
        }), []
    );

    // Getters and setters for In-Page instance and state
    const inPageRef = useRef(null);
    const [isInPageReady, setIsInPageReady] = useState(false);
    const [isProcessingPayment, setIsProcessingPayment] = useState(false);
    const isProcessingRef = useRef(false); // Track processing state without triggering re-renders
    const lastInitRef = useRef({planKey: null, cartTotal: null}); // Track last init params

    // Define default plan and selected plan
    const availableFeePlans = gatewaySettings.fee_plans_settings || {};

    // Define the first plan as default and store it in state
    let default_plan = '';
    if (!isLoading && Object.keys(availableFeePlans || {}).length > 0) {
        default_plan = Object.keys(availableFeePlans)[0];
    }
    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);
    const plan = !isLoading
        ? availableFeePlans?.[selectedFeePlan] ?? availableFeePlans?.[default_plan]
        : null;

    // Synchronize selectedFeePlan with the store
    useEffect(() => {
        if (selectedFeePlan) {
            dispatch(storeKey).setSelectedFeePlan(selectedFeePlan);
        }
    }, [selectedFeePlan, storeKey]);

    /**
     * Reset checkout state after payment modal is closed
     */
    const resetCheckoutState = useCallback(() => {
        console.log(`[${gatewaySettings.name}] ========== RESET CHECKOUT STATE ==========`);
        console.log(gatewaySettings.name + ': Resetting checkout state...', {
            hadInstance: !!inPageRef.current,
            wasReady: isInPageReady,
            isProcessing: isProcessingRef.current
        });

        try {
            // Reinit "processing" state of checkout
            dispatch(CHECKOUT_STORE_KEY).__internalSetProcessing(false);

            // Reinit "idle" state of checkout
            dispatch(CHECKOUT_STORE_KEY).__internalSetIdle();

            console.log(gatewaySettings.name + ': Checkout state reset successfully');
        } catch (error) {
            console.warn('Could not reset checkout state:', error);
        }

        // Reset processing state FIRST to allow new payment attempt
        console.log(gatewaySettings.name + ': Setting isProcessingRef to false');
        isProcessingRef.current = false;
        setIsProcessingPayment(false);

        // Clean up and reset In-Page instance to ensure fresh state
        if (inPageRef.current && typeof inPageRef.current.unmount === 'function') {
            try {
                console.log(gatewaySettings.name + ': Unmounting In-Page for fresh restart');
                inPageRef.current.unmount();
                inPageRef.current = null;
                setIsInPageReady(false);
                console.log(gatewaySettings.name + ': Instance unmounted, set to null, isReady set to false');

                // Reset lastInitRef to force reinit on next attempt
                // Use setTimeout to give DOM time to clean up
                console.log(gatewaySettings.name + ': Scheduling lastInitRef reset in 200ms');
                setTimeout(() => {
                    lastInitRef.current = {planKey: null, cartTotal: null};
                    console.log(gatewaySettings.name + ': lastInitRef reset complete');
                }, 200);
            } catch (e) {
                console.warn('Error unmounting In-Page on reset:', e);
            }
        } else {
            console.log(gatewaySettings.name + ': No instance to unmount');
        }

        console.log(gatewaySettings.name + ': Ready for new payment attempt');
        console.log(`[${gatewaySettings.name}] ========== RESET COMPLETE ==========`);
    }, [CHECKOUT_STORE_KEY, gatewaySettings.name]);

    /**
     * Initialize Alma In-Page Iframe
     */
    const initializeInPage = useCallback((total_price) => {
        console.log(`[${gatewaySettings.gateway_name}] ========== INITIALIZE IN-PAGE ==========`);
        console.log('Initializing Alma In-Page Iframe for', gatewaySettings.gateway_name, {
            total_price,
            isProcessing: isProcessingRef.current,
            hasInstance: !!inPageRef.current,
            hasPlan: !!plan
        });

        // Don't re-initialize if a payment is in progress (use ref to avoid re-creating callback)
        if (isProcessingRef.current) {
            console.log(`[${gatewaySettings.gateway_name}] Payment in progress, skipping re-initialization`);
            return;
        }

        // Clean up previous instance if exists
        if (inPageRef.current && typeof inPageRef.current.unmount === 'function') {
            try {
                console.log(`[${gatewaySettings.gateway_name}] Unmounting previous instance`);
                inPageRef.current.unmount();
                inPageRef.current = null;
            } catch (e) {
                console.info('Error unmounting previous instance:', e);
            }
        }

        // Don't initialize if no plan is selected
        if (!plan) {
            console.warn(`[${gatewaySettings.gateway_name}] No plan available`);
            setIsInPageReady(false);
            return;
        }

        // Use unique selector per gateway to avoid conflicts
        const containerSelector = `#alma-inpage-container-${gatewaySettings.gateway_name}`;
        console.log(`[${gatewaySettings.gateway_name}] Using selector: ${containerSelector}`);

        try {
            console.log(`[${gatewaySettings.gateway_name}] Calling Alma.InPage.initialize...`);
            inPageRef.current = Alma.InPage.initialize({
                merchantId: almaSettings.merchant_id,
                amountInCents: total_price,
                installmentsCount: plan.installmentsCount,
                selector: containerSelector,
                deferredDays: plan.deferredDays,
                deferredMonths: plan.deferredMonths,
                environment: almaSettings.environment,
                locale: almaSettings.language,
            });

            setIsInPageReady(true);
            console.log(`[${gatewaySettings.gateway_name}] In-Page initialized successfully`);
            console.log(`[${gatewaySettings.gateway_name}] ========== INITIALIZE COMPLETE ==========`);
        } catch (error) {
            console.error(`[${gatewaySettings.gateway_name}] Failed to initialize:`, error);
            setIsInPageReady(false);
            inPageRef.current = null;
        }
    }, [plan, almaSettings, gatewaySettings.gateway_name]);

    /**
     * Prepare payment data onPaymentSetup
     */
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            console.log(gatewaySettings.name + ': In-Page Payment Setup starting...', {
                isProcessing: isProcessingRef.current,
                hasInstance: !!inPageRef.current,
                isReady: isInPageReady,
                gateway: gatewaySettings.gateway_name
            });

            if (isProcessingRef.current) {
                console.log(gatewaySettings.name + ': Payment already processing, skipping...');
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            // Initialize In-Page instance if it doesn't exist yet (first time this gateway is used)
            if (!inPageRef.current && plan && cartTotal) {
                console.log(gatewaySettings.name + ': First time use - initializing In-Page instance...', {
                    planKey: plan.planKey,
                    cartTotal,
                    hasAlmaGlobal: typeof Alma !== 'undefined',
                    hasInPageAPI: typeof Alma?.InPage !== 'undefined'
                });

                lastInitRef.current = {planKey: plan.planKey, cartTotal: cartTotal};

                console.log(gatewaySettings.name + ': Calling initializeInPage()...');
                initializeInPage(cartTotal);

                console.log(gatewaySettings.name + ': Waiting 500ms for initialization...');
                // Wait a bit for initialization to complete
                await new Promise(resolve => setTimeout(resolve, 500));

                console.log(gatewaySettings.name + ': After 500ms wait, checking instance...', {
                    hasInstance: !!inPageRef.current,
                    instanceType: inPageRef.current ? typeof inPageRef.current : 'null'
                });

                // Check if initialization succeeded by verifying inPageRef.current
                // (Don't check isInPageReady as it may not be updated yet due to React state batching)
                if (!inPageRef.current) {
                    console.error(gatewaySettings.name + ': Failed to initialize In-Page instance after 500ms wait');
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment widget initialization failed. Please try again.',
                    };
                }

                console.log(gatewaySettings.name + ': In-Page instance ready after initialization');
            } else if (!inPageRef.current) {
                // No instance and no plan/cartTotal to initialize
                console.error(gatewaySettings.name + ': Cannot initialize - missing plan or cartTotal', {
                    hasPlan: !!plan,
                    hasCartTotal: !!cartTotal
                });
                console.error(gatewaySettings.name + ': ❌ RETURNING ERROR - WooCommerce will use fallback');
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Payment widget is not ready. Please wait a moment and try again.',
                };
            } else {
                console.log(gatewaySettings.name + ': Instance already exists, continuing with payment setup', {
                    hasInstance: !!inPageRef.current
                });
            }

            isProcessingRef.current = true;
            setIsProcessingPayment(true);

            try {
                const nonceKey = almaSettings.nonce_key;
                const paymentMethodData = {
                    [nonceKey]: `${almaSettings.nonce_value}`,
                    alma_plan_key: String(selectedFeePlan || ''),
                    payment_method: String(gatewaySettings.gateway_name || ''),
                };

                console.log(gatewaySettings.name + ': ✅ RETURNING SUCCESS with paymentMethodData:', paymentMethodData);

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: paymentMethodData,
                    }
                };

            } catch (error) {
                console.error(gatewaySettings.name + ': Payment setup error:', error);
                console.error(gatewaySettings.name + ': ❌ RETURNING ERROR - WooCommerce will use fallback');
                isProcessingRef.current = false;
                setIsProcessingPayment(false);
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: error.message || 'Payment initialization failed',
                };
            }
        });

        return () => unsubscribe();
    }, [onPaymentSetup, selectedFeePlan, gatewaySettings.gateway_name, gatewaySettings.name, almaSettings.nonce_key, almaSettings.nonce_value, emitResponse.responseTypes, isInPageReady, plan, cartTotal, initializeInPage]);

    /**
     * Open In-Page modal onCheckoutSuccess
     */
    useEffect(() => {
        const unsubscribeSuccess = onCheckoutSuccess(async (checkoutResponse) => {
            console.log(`[${gatewaySettings.name}] ========== ON CHECKOUT SUCCESS CALLED ==========`);
            console.log(`[${gatewaySettings.gateway_name}] Gateway: ${gatewaySettings.gateway_name}, Name: ${gatewaySettings.name}`);
            console.log('Checkout API call successful:', checkoutResponse);

            try {
                // Get payment details from response
                const paymentDetails = checkoutResponse.processingResponse?.paymentDetails;
                const almaPaymentId = paymentDetails?.alma_payment_id;

                if (!almaPaymentId) {
                    console.error('No Alma payment ID in response');
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment initialization failed - no payment ID',
                    };
                }

                if (!inPageRef.current || !isInPageReady) {
                    console.error('In-Page not ready', {
                        hasInstance: !!inPageRef.current,
                        isReady: isInPageReady,
                        isProcessing: isProcessingRef.current
                    });
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment widget not initialized',
                    };
                }

                console.log('Opening Alma In-Page payment modal...', {
                    hasInstance: !!inPageRef.current,
                    isReady: isInPageReady,
                    paymentId: almaPaymentId
                });

                try {
                    const paymentResult = await inPageRef.current.startPayment({
                        paymentId: almaPaymentId,
                        onUserCloseModal: () => {
                            console.log('Payment modal closed by user');
                            resetCheckoutState();
                        }
                    });

                    // Check payment result status
                    if (paymentResult && paymentResult.status === 'success') {
                        console.log('Payment completed successfully');
                        setIsProcessingPayment(false);

                        // Redirect to order received page
                        if (!inPageRef.current && checkoutResponse.redirectUrl) {
                            window.location.href = checkoutResponse.redirectUrl;
                        } else {
                            const orderId = checkoutResponse.orderId || paymentResult.orderId;
                            const orderKey = checkoutResponse.orderKey || '';
                            window.location.href = `/checkout/order-received/${orderId}/?key=${orderKey}`;
                        }
                    } else if (paymentResult && paymentResult.status === 'error') {
                        console.error('Payment error:', paymentResult.error);
                        resetCheckoutState();
                        alert(paymentResult.error?.message || 'Payment failed. Please try again.');
                    } else if (paymentResult && paymentResult.status === 'cancelled') {
                        console.log('⚠Payment cancelled by user');
                        resetCheckoutState();
                    }

                } catch (paymentError) {
                    console.error('Payment modal error:', paymentError);
                    resetCheckoutState();

                    if (paymentError.code === 'user_cancelled') {
                        console.log('User cancelled payment');
                    } else {
                        alert(paymentError.message || 'Payment failed. Please try again.');
                    }
                }

            } catch (error) {
                console.error('Failed to open payment modal:', error);
                resetCheckoutState();
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Failed to open payment popup: ' + error.message,
                };
            }
        });

        return () => unsubscribeSuccess();
    }, [onCheckoutSuccess, isInPageReady, emitResponse.responseTypes, resetCheckoutState]);

    /**
     * Handle checkout failure onCheckoutFail
     */
    useEffect(() => {
        const unsubscribeFail = onCheckoutFail((error) => {
            console.error('Checkout failed:', error);

            // Unmount the In-Page instance if it exists
            if (inPageRef.current && typeof inPageRef.current.unmount === 'function') {
                try {
                    inPageRef.current.unmount();
                } catch (e) {
                    console.warn('Failed to unmount In-Page instance on fail:', e);
                }
            }

            // Reset checkout state
            resetCheckoutState();

            // Optionally, show an error to the user
            alert('Payment failed. Please try again.');
        });

        return () => unsubscribeFail();
    }, [onCheckoutFail, resetCheckoutState]);

    /**
     * Initialize or re-initialize In-Page when plan or cart total changes
     */
    useEffect(() => {
        console.log(`[${gatewaySettings.gateway_name}] useEffect triggered`, {
            isProcessing: isProcessingRef.current,
            isLoading,
            hasPlan: !!plan,
            hasCartTotal: !!cartTotal,
            hasInstance: !!inPageRef.current,
            isReady: isInPageReady,
            lastInit: lastInitRef.current
        });

        // Don't reinit if a payment is currently being processed (by any gateway)
        if (isProcessingRef.current) {
            console.log(gatewaySettings.gateway_name + ': Skipping reinit - payment in progress');
            return;
        }

        // Initialize or re-initialize In-Page if plan or cart total changed OR instance is missing
        if (!isLoading && plan && cartTotal) {
            const planKey = plan.planKey;

            // Check if this gateway was ever initialized
            const wasInitialized = lastInitRef.current.planKey !== null || lastInitRef.current.cartTotal !== null;

            // ONLY reinit if this gateway was already initialized before
            const needsReinit = wasInitialized && (
                lastInitRef.current.planKey !== planKey ||
                lastInitRef.current.cartTotal !== cartTotal ||
                !inPageRef.current ||
                !isInPageReady
            );

            console.log(`[${gatewaySettings.gateway_name}] Checking if needs reinit`, {
                needsReinit,
                wasInitialized,
                lastPlanKey: lastInitRef.current.planKey,
                currentPlanKey: planKey,
                lastCartTotal: lastInitRef.current.cartTotal,
                currentCartTotal: cartTotal,
                hasInstance: !!inPageRef.current,
                isReady: isInPageReady
            });

            if (needsReinit) {
                console.log('Reinitializing In-Page widget', {
                    reason: !inPageRef.current ? 'instance null after init' :
                        !isInPageReady ? 'not ready after init' :
                            lastInitRef.current.planKey !== planKey ? 'plan changed' :
                                'cart changed',
                    gateway: gatewaySettings.gateway_name,
                    planKey,
                    cartTotal
                });
                lastInitRef.current = {planKey, cartTotal};
                initializeInPage(cartTotal);
            } else {
                console.log(`[${gatewaySettings.gateway_name}] No reinit needed`, {
                    wasInitialized,
                    reason: !wasInitialized ? 'never initialized - waiting for user selection' : 'all conditions met'
                });
            }
        } else {
            console.log(`[${gatewaySettings.gateway_name}] Conditions not met:`, {
                isLoading,
                hasPlan: !!plan,
                hasCartTotal: !!cartTotal
            });
        }
    }, [plan?.planKey, cartTotal, isLoading, initializeInPage, isInPageReady, gatewaySettings.gateway_name]);

    const displayInstallments = gatewaySettings.is_pay_now ? 'none' : 'block';

    // Render loading state or Alma In-Page block
    return isLoading ? (
        <div>Loading payment options...</div>
    ) : (
        <>
            <AlmaBlock
                hasInPage={almaSettings.is_in_page}
                isPayNow={gatewaySettings.is_pay_now}
                totalPrice={cartTotal}
                gatewaySettings={gatewaySettings}
                selectedFeePlan={plan.planKey}
                setSelectedFeePlan={setSelectedFeePlan}
                plans={availableFeePlans}
            />
            {/* Informative In-Page Widget - Unique per gateway */}
            <div
                id={`alma-inpage-container-${gatewaySettings.gateway_name}`}
                style={{display: displayInstallments}}
            ></div>
            {/* Loading state */}
            {isProcessingPayment}
        </>
    );
}
