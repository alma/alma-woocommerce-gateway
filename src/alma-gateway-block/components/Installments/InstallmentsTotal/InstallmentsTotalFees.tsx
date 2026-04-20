import * as React from "react";
import "./InstallmentsTotal.css";
import {FormattedNumber} from "react-intl";
import {__} from "@wordpress/i18n";


type Props = {
    customerFees: number;
};

export const InstallmentsTotalFees: React.FC<Props> = ({customerFees}) => (
        <div className={"alma-installmentsTotal-fees"}>
            <div>{__('Payment costs', 'alma-gateway-for-woocommerce')}</div>
            <div className={"alma-installmentsTotal-feesNumbers"}><FormattedNumber
                    value={customerFees / 100}
                    style="currency"
                    currency="EUR"
            />
            </div>
        </div>
);
