import {render} from '@wordpress/element';
import AlmaWidget from './AlmaWidget';
import './alma-widget-block-view.css';

(function ($) {
    const almaWidgetDivId = 'alma-widget-container';

    function waitAlmaWidgetDiv(selector) {
        return new Promise((resolve) => {
            const observer = new MutationObserver(() => {
                if ($(selector).length > 0) {
                    resolve($(selector));
                    observer.disconnect();
                }
            });

            observer.observe(document.body, {childList: true, subtree: true});
        });
    }

    function addAlmaWidget() {
        waitAlmaWidgetDiv('#' + almaWidgetDivId).then(() => {
            const almaContainer = document.getElementById(almaWidgetDivId);
            if (almaContainer) {
                render(<AlmaWidget/>, almaContainer);
            }
        });
    }

    addAlmaWidget();
})(jQuery);

