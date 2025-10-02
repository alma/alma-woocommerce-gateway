/* global woocommerce_admin */
(function ($, woocommerce_admin) {
    $(function () {
        // Toggle fee plans on/off.
        $('.wc_gateways').on(
            'click',
            '.wc-alma-toggle-fee-plan-enabled',
            function () {
                const click = $(this),
                    row = click.closest('tr'),
                    toggle = row.find('.woocommerce-input-toggle'),
                    hiddenInput = row.find('input[type="hidden"]');

                // Loading state
                toggle.addClass('woocommerce-input-toggle--loading');

                // What's the current state?
                const isEnabled = hiddenInput.val();
                const newValue = isEnabled === '1' ? '0' : '1';

                // Toggle state
                if ('1' === newValue) {
                    toggle.removeClass(
                        'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled'
                    );
                    toggle.addClass(
                        'woocommerce-input-toggle--enabled'
                    );
                    toggle.removeClass(
                        'woocommerce-input-toggle--loading'
                    );
                } else {
                    toggle.removeClass(
                        'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled'
                    );
                    toggle.addClass(
                        'woocommerce-input-toggle--disabled'
                    );
                    toggle.removeClass(
                        'woocommerce-input-toggle--loading'
                    );
                }
                hiddenInput.val(newValue);
                $(".woocommerce-save-button").removeAttr("disabled");

                return false;
            }
        );
    });

})(jQuery, woocommerce_admin);
