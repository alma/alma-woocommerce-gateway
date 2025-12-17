/*
 * Alma Frontend Widget Implementation
 * This script initializes the Alma payment widget on the frontend.
 * @see includes/Infrastructure/Helper/AssetsHelper.php
 */
(function ($) {
    $(function () {
        /**
         * Hide or display the payment plan details based on the selected payment method.
         */
        function displayInstallmentsPlanForPaymentMethods() {
            let $checked = $('input[name="alma_plan_key"]:checked');

            $('.alma_woocommerce_gateway_checkout_plan').hide();

            if ($checked.length) {
                let value = $checked.val();
                let id = '#alma-checkout-plan-' + value;
                $(id).show();
            }
        }

        function selectFirstPaymentMethod(paymentBox) {
            let $first = paymentBox.find("input[name='alma_plan_key']").first();
            $first.prop('checked', true);
            displayInstallmentsPlanForPaymentMethods();
        }

        $(document).on('change', 'input[name="payment_method"]', function() {
            if ($(this).val().startsWith('alma_')) {
                let paymentBox = $(this).nextAll('.payment_box').first();
                selectFirstPaymentMethod(paymentBox);
            }
        });

        $(document).on('change', 'input[name="alma_plan_key"]', function() {
            displayInstallmentsPlanForPaymentMethods();
        });

        if ($('input[name="payment_method"]:checked').val()?.startsWith('alma_')) {
            let paymentBox = $('input[name="payment_method"]:checked').nextAll('.payment_box').first();
            selectFirstPaymentMethod(paymentBox);
        } else {
            displayInstallmentsPlanForPaymentMethods();
        }
    })
})(jQuery);
