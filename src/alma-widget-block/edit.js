import {useBlockProps} from '@wordpress/block-editor';
import {__} from '@wordpress/i18n';

import './alma-widget-block.css';

const Edit = () => {
    return (
        <div {...useBlockProps()} data-testid="alma-widget-container">
            <div id="alma-widget-payment-plans-main-container">

                <div id="alma-payment-plans-eligibility-line" data-testid="widget-container">

                    <div id="payment-plans-title" role="heading" aria-level="2" className="sr-only">Available payment
                        options
                    </div>

                    <button type="button" aria-label="Open Alma payment options to find out more" aria-haspopup="dialog"
                            aria-describedby="payment-info-text"
                            className="_knowMore_v4hon_179 _clickable_v4hon_171 _monochrome_v4hon_82 alma-payment-plans-know-more-action">

                        <svg width="42" height="24" viewBox="0 0 352 120" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M333.24 28.3462V38.4459C327.504 31.1018 319.176 26.5132 309.288 26.5132C290.208 26.5132 275.424 43.5497 275.424 64.5757C275.424 85.6018 290.208 102.638 309.288 102.638C319.872 102.638 328.668 97.3908 334.416 89.1241V100.817H352.668V28.3462H333.24ZM314.028 84.4876C303.42 84.4876 294.828 75.574 294.828 64.5757C294.828 53.5775 303.42 44.6639 314.028 44.6639C324.636 44.6639 333.228 53.5775 333.228 64.5757C333.228 75.574 324.636 84.4876 314.028 84.4876ZM109.5 8.23073H128.916V100.805H109.5V8.23073ZM151.248 59.7356C151.248 39.8117 163.5 26.5252 180.468 26.5252C191.004 26.5252 199.332 31.1976 204.348 39.1648C209.376 31.1976 217.692 26.5252 228.228 26.5252C245.196 26.5252 257.448 39.8117 257.448 59.7356V100.817H238.032V57.639C238.032 49.8635 232.872 44.7957 226.044 44.7957C219.216 44.7957 214.056 49.8755 214.056 57.639V100.817H194.64V57.639C194.64 49.8635 189.48 44.7957 182.652 44.7957C175.824 44.7957 170.664 49.8755 170.664 57.639V100.817H151.248V59.7356ZM74.34 29.101C69.744 11.9088 60.0241 6.40967 50.772 6.40967C41.5201 6.40967 31.8 11.9088 27.204 29.101L7.24805 100.829H26.916C30.12 88.8485 39.996 82.1753 50.772 82.1753C61.548 82.1753 71.424 88.8605 74.6281 100.829H94.3081L74.34 29.101ZM50.772 65.4623C44.508 65.4623 38.8321 67.8345 34.6441 71.6803L45.924 29.9397C47.0041 25.9501 48.6001 24.6802 50.784 24.6802C52.9681 24.6802 54.5641 25.9501 55.6441 29.9397L66.912 71.6803C62.724 67.8345 57.036 65.4623 50.772 65.4623Z"
                                fill="#000"></path>
                        </svg>
                    </button>


                    <div role="listbox" aria-label="Available payment options"
                         id="alma-payment-plans-eligibility-options"
                         className="_paymentPlans_v4hon_19 ">

                        <button type="button" role="option" aria-selected="true"
                                aria-describedby="payment-info-text" aria-label="Payment option Pay now"
                                aria-disabled="false" tabIndex="0"
                                className="_planButton_v4hon_29 _active_v4hon_102 alma-payment-plans-active-option _monochrome_v4hon_82">2x
                        </button>

                        <button id="active" type="button" role="option" aria-selected="true"
                                aria-describedby="payment-info-text" aria-label="Payment option Pay now"
                                aria-disabled="false" tabIndex="0"
                                className="_planButton_v4hon_29 _active_v4hon_102 alma-payment-plans-active-option _monochrome_v4hon_82">3x
                        </button>
                    </div>

                </div>
                <div id="payment-info-text" className="_info_v4hon_121 alma-payment-plans-payment-info">
                    <p>4 x 112,50&nbsp;€&nbsp;({__('Without fees', 'alma-gateway-for-woocommerce')})</p>
                </div>
            </div>


        </div>
    );
};

export default Edit;