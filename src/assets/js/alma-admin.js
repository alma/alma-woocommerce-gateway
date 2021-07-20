var select_alma_fee_plan_ids = select_alma_fee_plan_ids || [];
jQuery(document).ready(function() {
    /**
     * Disable or enable menuItem Option depending to the checkbox state
     *
     * @param {jQuery} $checkbox
     * @param {jQuery} $items
     */
    function toggleItems($checkbox, $items) {
        if ($checkbox.is(":checked")) {
            $items.addClass('alma_option_enabled');
            $items.removeClass('alma_option_disabled');
        } else {
            $items.removeClass('alma_option_enabled');
            $items.addClass('alma_option_disabled');
        }
    }

    /**
     * @param {string} optionValue as plan
     * @return {string}
     */
    function optionStatusClass(optionValue) {
        return jQuery('#woocommerce_alma_enabled_' + optionValue).is(":checked") ? 'alma_option_enabled' : 'alma_option_disabled';
    }

    jQuery.widget('custom.almaSelectMenu', jQuery.ui.selectmenu, {
        _renderButtonItem: function (item) {
            return jQuery('<span>', {
                text: item.label,
                id: this.options.id + '_button_' + item.value,
                class: "ui-selectmenu-text " + optionStatusClass(item.value),
                style: 'border: 1px solid #8c8f94; background: white; border-radius: 4px; line-height: 1; width: 400px; font-size: 14px;'
            });
        },

        _renderItem: function ($ul, item) {
            var $li = jQuery('<li>', {
                class: "ui-menu-item li_" + item.value
            });
            var $wrapper = jQuery('<div>', {
                text: item.label,
                class: 'ui-menu-item-wrapper ' + optionStatusClass(item.value),
                id: this.options.id + '_item_' + item.value
            });
            $li.append($wrapper);
            return $li.appendTo($ul);
        }
    });

    function validId(id) {
        return ["number", "string"].includes(typeof id) && !!String(id).trim();
    }

    select_alma_fee_plan_ids.filter(validId).forEach(function (id) {
        var $select = jQuery('#' + id);
        var previousPlan = $select.val();
        $select.almaSelectMenu({
            id: id,
            change: function (event) {
                var plan = $select.val();
                var $inputMin = jQuery('#woocommerce_alma_min_amount_' + previousPlan);
                var val = parseInt($inputMin.val());
                var min = parseInt($inputMin.attr('min'));
                var max = parseInt($inputMin.attr('max'));
                if (val < min || val > max) {
                    $select.val(previousPlan);
                    event.stopPropagation();
                    $select.almaSelectMenu('refresh');
                    jQuery('button[type=submit]').click();
                    return false;
                }
                jQuery('.alma_fee_plan').hide();
                var $sections = jQuery('.alma_fee_plan_' + plan);
                $sections.show();
                $sections.effect('highlight', 1500);
                $sections.find('b').effect('highlight', 5000);
                previousPlan = plan;
            }
        });
    })
    jQuery('[id^=woocommerce_alma_enabled_]').change(function () {
        var $checkbox = jQuery(this);
        var plan = $checkbox.attr('id').substring(25);
        select_alma_fee_plan_ids.filter(validId).forEach(function (id) {
            var selector = '#' + id + '_item_' + plan + ", #" + id + '_button_' + plan;
            toggleItems($checkbox, jQuery(selector));
        });
    });
});
