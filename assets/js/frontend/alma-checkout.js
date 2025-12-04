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
                let value = $checked.val(); // ex: "general_2_0_0"
                let id = '#alma-checkout-plan-' + value;

                $(id).show();
            }
        }

        function selectFirstPaymentMethod(paymentBox) {
            let $first = paymentBox.find("input[name='alma_plan_key']").first();
            $first.prop('checked', true);
        }

        $(document).on('change', 'input[name="payment_method"]', function() {
            let paymentBox = $(this).nextAll('.payment_box');
            selectFirstPaymentMethod(paymentBox);
        });

        $(document).on('change', 'input[name="alma_plan_key"]', function() {
            displayInstallmentsPlanForPaymentMethods();
        });

        displayInstallmentsPlanForPaymentMethods();
    })
})(jQuery);
