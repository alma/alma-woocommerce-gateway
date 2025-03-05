import React from "react";
import {FormattedMessage, FormattedNumber} from "react-intl";
import "./InstallmentsTotal.css";
import {__} from "@wordpress/i18n";

type Props = {
    totalAmount: number;
    customerFees: number;
};

export const InstallmentsTotal: React.FC<Props> = ({totalAmount, customerFees}) => {
    const totalAmountIncludingFees = (totalAmount) + (customerFees / 100)

        return (
            <div className={"total"}>
                <div>
                    {__('Total TTC', 'alma-gateway-for-woocommerce')}
                </div>
                <div>
                    <FormattedNumber
                        value={totalAmountIncludingFees}
                        style="currency"
                        currency="EUR"
                    />
                </div>
            </div>)
    }
;
