/*
 * Alma Frontend Widget Implementation
 * This script initializes the Alma payment widget on the frontend.
 * @see includes/WooCommerce/Helper/ShortcodeWidgetHelper.php
 */

(function ($) {
    $(function () {

        /**
         * Initialize the Alma widget for displaying payment plans.
         */
        function initializeAlmaWidget(price) {

            console.log(price);


            let containerSelector = alma_widget_settings.widget_selector;
            if ($(containerSelector).length === 0) {
                containerSelector = alma_widget_settings.widget_default_selector;
            }

            // Ensure we had plans to display, then init the widget
            if (Alma.Widgets.PaymentPlans && Object.keys(Alma.Widgets.PaymentPlans).length > 0) {
                const widgets = Alma.Widgets.initialize(
                    alma_widget_settings.merchant_id,
                    Alma.ApiMode.TEST,
                );

                widgets.add(Alma.Widgets.PaymentPlans, {
                    container: containerSelector,
                    purchaseAmount: price || alma_widget_settings.price,
                    locale: alma_widget_settings.language,
                    hideIfNotEligible: alma_widget_settings.hide_if_not_eligible,
                    transitionDelay: alma_widget_settings.transition_delay,
                    monochrome: alma_widget_settings.monochrome,
                    hideBorder: alma_widget_settings.hide_border,
                    plans: alma_widget_settings.fee_plan_list
                })
            }
        }

        /**
         * Listen for changes in the cart or checkout page to reinitialize the widget.
         */
        function listenToChanges() {

            // Listen for changes in the cart totals to reinitialize the widget
            $(document.body).on('updated_cart_totals', function () {
                const price =
                    parseFloat($('.cart-subtotal .woocommerce-Price-amount').text().replace(/[^0-9.-]+/g, '')
                    );
                initializeAlmaWidget(price);
            });

            // Listen for changes in the checkout page
            $(document.body).on('found_variation', function (event, variation) {
                const price = Alma.Utils.priceToCents(variation.display_price);
                initializeAlmaWidget(price);
            });
        }

        $(function () {
            initializeAlmaWidget();
            listenToChanges();
        });
    })
})(jQuery);
