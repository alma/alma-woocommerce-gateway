/* global woocommerce_admin */
(function ($, woocommerce_admin) {
    $(function () {
        // Toggle fee plans on/off.
        $('.wc_gateways').on(
            'click',
            '.wc-alma-toggle-fee-plan-enabled',
            function () {
                const $link = $(this),
                    $row = $link.closest('tr'),
                    $toggle = $link.find('.woocommerce-input-toggle');
                console.log('test ok');
                const data = {
                    action: 'alma_toggle_fee_plan_enabled',
                    security: alma_settings.nonce,
                    fee_plan_key: $row.data('fee_plan_key'),
                };

                $toggle.addClass('woocommerce-input-toggle--loading');

                $.ajax({
                    url: alma_settings.ajax_url,
                    data: data,
                    dataType: 'json',
                    type: 'POST',
                    success: function (response) {
                        if (true === response.data) {
                            $toggle.removeClass(
                                'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled'
                            );
                            $toggle.addClass(
                                'woocommerce-input-toggle--enabled'
                            );
                            $toggle.removeClass(
                                'woocommerce-input-toggle--loading'
                            );
                        } else if (false === response.data) {
                            $toggle.removeClass(
                                'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled'
                            );
                            $toggle.addClass(
                                'woocommerce-input-toggle--disabled'
                            );
                            $toggle.removeClass(
                                'woocommerce-input-toggle--loading'
                            );
                        } else if ('needs_setup' === response.data) {
                            window.location.href = $link.attr('href');
                        }
                    },
                });

                return false;
            }
        );
    });

})(jQuery, woocommerce_admin);
