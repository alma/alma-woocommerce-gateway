import {dispatch, useSelect} from '@wordpress/data';
import {useCallback, useEffect, useRef, useState} from '@wordpress/element';
import {AlmaBlock} from "./alma-block-component.tsx";

export const DisplayAlmaInPageBlock = (props) => {
    const {
        eventRegistration,
        emitResponse,
        gateway,
        storeKey,
        cartTotal,
        setInPage,
        inPage,
    } = props;

    // WC Blocks API compatibility:
    // WC 10+ (Blocks 9+): onPaymentSetup, onCheckoutSuccess, onCheckoutFail
    // WC 9.x (Blocks 7-8): onPaymentProcessing, onCheckoutAfterProcessingWithSuccess, onCheckoutAfterProcessingWithError
    const onPaymentSetup = eventRegistration.onPaymentSetup || eventRegistration.onPaymentProcessing;
    const onCheckoutSuccess = eventRegistration.onCheckoutSuccess || eventRegistration.onCheckoutAfterProcessingWithSuccess;
    const onCheckoutFail = eventRegistration.onCheckoutFail || eventRegistration.onCheckoutAfterProcessingWithError;

    // Get the Checkout Store Key
    const {CHECKOUT_STORE_KEY} = window.wc.wcBlocksData;

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

    // Define default plan and selected plan
    const availableFeePlans = gatewaySettings.fee_plans_settings || {};

    // Define the first plan as default and store it in state
    let default_plan = '';
    if (!isLoading && Object.keys(availableFeePlans || {}).length > 0) {
        default_plan = Object.keys(availableFeePlans)[0];
    }
    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);

    // Sync selectedFeePlan when default_plan becomes available after loading
    useEffect(() => {
        if (default_plan && !selectedFeePlan) {
            setSelectedFeePlan(default_plan);
        }
    }, [default_plan]);

    const plan = !isLoading
        ? availableFeePlans?.[selectedFeePlan] ?? availableFeePlans?.[default_plan]
        : null;

    // Synchronize selectedFeePlan with the store
    useEffect(() => {
        if (selectedFeePlan) {
            dispatch(storeKey).setSelectedFeePlan(selectedFeePlan);
        }
    }, [selectedFeePlan]);

    /**
     * Reset checkout state after payment modal is closed
     */
    const resetCheckoutState = useCallback(() => {
        try {
            const store = dispatch(CHECKOUT_STORE_KEY);
            if (typeof store.__internalSetProcessing === 'function') {
                // WC 10+ (Blocks 9+)
                store.__internalSetProcessing(false);
                store.__internalSetIdle();
            } else if (typeof store.setIdle === 'function') {
                // [WC-COMPAT 9.0-9.7] Start — Reset checkout state for WC 9.x
                // @see docs/WC97-SYNC-REGISTRATION-PATCH.md
                // Remove this branch when MIN_WOOCOMMERCE_VERSION >= 9.8
                if (typeof store.setComplete === 'function') {
                    store.setComplete(false);
                }
                store.setIdle();
                // [WC-COMPAT 9.0-9.7] End
            }
        } catch (error) {
            console.warn('Could not reset checkout state:', error);
        }

        setIsProcessingPayment(false);
    }, [CHECKOUT_STORE_KEY]);

    /**
     * Initialize Alma In-Page Iframe
     */
    const initializeInPage = useCallback((total_price) => {
        // Don't re-initialize if a payment is in progress
        if (isProcessingPayment) {
            return;
        }

        // Clean up previous instance if exists
        if (inPage && typeof inPage.unmount === 'function') {
            try {
                inPage.unmount();
            } catch (e) {
                console.info('Unmounting previous instance');
            }
        }

        // Don't initialize if no plan is selected
        if (!plan) {
            console.warn('No plan available');
            return;
        }

        try {
            inPageRef.current = Alma.InPage.initialize({
                merchantId: almaSettings.merchant_id,
                amountInCents: total_price,
                installmentsCount: plan.installmentsCount,
                selector: "#alma-inpage-container",
                deferredDays: plan.deferredDays,
                deferredMonths: plan.deferredMonths,
                environment: almaSettings.environment,
                locale: almaSettings.language,
            });

            // Store and share the instance in parent component state
            setInPage(inPageRef.current);
            setIsInPageReady(true);
        } catch (error) {
            console.error('Failed to initialize:', error);
            setIsInPageReady(false);
        }
    }, [plan, almaSettings, inPage, setInPage, isProcessingPayment]);

    /**
     * Prepare payment data onPaymentSetup
     */
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            if (isProcessingPayment) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            setIsProcessingPayment(true);

            try {
                const nonceKey = almaSettings.nonce_key;
                const paymentMethodData = {
                    [nonceKey]: `${almaSettings.nonce_value}`,
                    alma_plan_key: String(selectedFeePlan || ''),
                    payment_method: String(gatewaySettings.gateway_name || ''),
                };

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: paymentMethodData,
                    }
                };

            } catch (error) {
                console.error('Payment setup error:', error);
                setIsProcessingPayment(false);
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: error.message || 'Payment initialization failed',
                };
            }
        });

        return () => unsubscribe();
    }, [onPaymentSetup, selectedFeePlan, gatewaySettings.gateway_name, almaSettings.nonce_key, almaSettings.nonce_value, emitResponse.responseTypes, isProcessingPayment]);

    /**
     * Open In-Page modal onCheckoutSuccess
     */
    useEffect(() => {
        const unsubscribeSuccess = onCheckoutSuccess(async (checkoutResponse) => {

            try {
                // Get payment details from response
                const paymentDetails = checkoutResponse.processingResponse?.paymentDetails;
                const almaPaymentId = paymentDetails?.alma_payment_id;

                if (!almaPaymentId) {
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment initialization failed - no payment ID',
                    };
                }

                if (!inPage || !isInPageReady) {
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment widget not initialized',
                    };
                }

                const paymentResult = await new Promise((resolve) => {
                    let resolvedPromisePaymentResult = false;

                    inPage.startPayment({
                        paymentId: almaPaymentId,
                        onUserCloseModal: () => {
                            if (!resolvedPromisePaymentResult) {
                                resolvedPromisePaymentResult = true;
                                resetCheckoutState();
                                resolve({ status: 'cancelled' });
                            }
                        }
                    });
                });

                if (paymentResult.status === 'cancelled') {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment cancelled',
                    };
                }

                if (paymentResult.status === 'success') {
                    setIsProcessingPayment(false);

                    if (checkoutResponse.redirectUrl) {
                        window.location.href = checkoutResponse.redirectUrl;
                    } else {
                        const orderId = checkoutResponse.orderId;
                        const orderKey = checkoutResponse.orderKey || '';
                        window.location.href = `/checkout/order-received/${orderId}/?key=${orderKey}`;
                    }
                    return {
                        type: emitResponse.responseTypes.SUCCESS,
                    };
                }

                if (paymentResult.status === 'error') {
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: paymentResult.error?.message || 'Payment failed',
                    };
                }

            } catch (error) {
                resetCheckoutState();
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Failed to open payment popup: ' + error.message,
                };
            }
        });

        return () => unsubscribeSuccess();
    }, [onCheckoutSuccess, isInPageReady, emitResponse.responseTypes, resetCheckoutState, inPage]);

    /**
     * Handle checkout failure onCheckoutFail
     */
    useEffect(() => {
        const unsubscribeFail = onCheckoutFail((error) => {
            // Unmount the In-Page instance if it exists
            if (inPage && typeof inPage.unmount === 'function') {
                try {
                    inPage.unmount();
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
    }, [onCheckoutFail, inPage, resetCheckoutState]);

    /**
     * Initialize or re-initialize In-Page when plan or cart total changes
     */
    const lastInitRef = useRef({planKey: null, cartTotal: null});

    useEffect(() => {
        // Initialize or re-initialize In-Page if plan or cart total changed
        if (!isLoading && plan && cartTotal) {
            const planKey = plan.planKey;

            if (lastInitRef.current.planKey !== planKey || lastInitRef.current.cartTotal !== cartTotal) {
                lastInitRef.current = {planKey, cartTotal};
                initializeInPage(cartTotal);
            }
        }
    }, [plan?.planKey, cartTotal, isLoading, initializeInPage]);

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
            {/* Informative In-Page Widget */}
            <div
                id="alma-inpage-container"
                style={{display: displayInstallments}}
            ></div>
            {/* Loading state */}
            {isProcessingPayment}
        </>
    );
}
