/*
 * Alma Frontend Widget Implementation
 * This script initializes the Alma payment widget on the frontend.
 * @see includes/Infrastructure/Helper/AssetsHelper.php
 */
(function ($) {
    $(function () {

        /**
         * Move to alma-checkout.js
         * Need to finish
         */
        function displayInstallmentsPlanForPaymentMethods() {
            $('#payment_method_alma_pnx_gateway').on('change', function () {
                if (this.checked) {
                    $('.alma_woocommerce_gateway_checkout_plan').hide();
                    $(this).closest('li.wc_payment_method').find('#alma-checkout-plan-general_3_0_0').show()
                }
            })
        }

        $(function () {
            displayInstallmentsPlanForPaymentMethods();
        });
    })
})(jQuery);
