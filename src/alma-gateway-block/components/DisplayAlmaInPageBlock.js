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

    const {onPaymentSetup, onCheckoutSuccess, onCheckoutFail} = eventRegistration;

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
        console.log(gatewaySettings.name + ': Resetting checkout state...');

        try {
            // Reinit "processing" state of checkout
            dispatch(CHECKOUT_STORE_KEY).__internalSetProcessing(false);

            // Reinit "idle" state of checkout
            dispatch(CHECKOUT_STORE_KEY).__internalSetIdle();

            console.log(gatewaySettings.name + ': Checkout state reset successfully');
        } catch (error) {
            console.warn('Could not reset checkout state:', error);
        }

        setIsProcessingPayment(false);
    }, [CHECKOUT_STORE_KEY]);

    /**
     * Initialize Alma In-Page Iframe
     */
    const initializeInPage = useCallback((total_price) => {
        console.log('Initializing Alma In-Page Iframe...');

        // Don't re-initialize if a payment is in progress
        if (isProcessingPayment) {
            console.log('Payment in progress, skipping re-initialization');
            return;
        }

        // Clean up previous instance if exists
        if (inPage && typeof inPage.unmount === 'function') {
            try {
                console.log('Unmounting previous instance');
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
            console.log('In-Page initialized');
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
            console.log(gatewaySettings.name + ': In-Page Payment Setup starting...');

            if (isProcessingPayment) {
                console.log(gatewaySettings.name + ': Payment already processing, skipping...');
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

                if (!inPage || !isInPageReady) {
                    console.error('In-Page not ready');
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment widget not initialized',
                    };
                }

                console.log('Opening Alma In-Page payment modal...');

                try {
                    const paymentResult = await inPage.startPayment({
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
                        if (!inPage && checkoutResponse.redirectUrl) {
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
    }, [onCheckoutSuccess, isInPageReady, emitResponse.responseTypes, resetCheckoutState, inPage]);

    /**
     * Handle checkout failure onCheckoutFail
     */
    useEffect(() => {
        const unsubscribeFail = onCheckoutFail((error) => {
            console.error('Checkout failed:', error);

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
                console.log('Plan or cart changed, reinitializing In-Page widget');
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
