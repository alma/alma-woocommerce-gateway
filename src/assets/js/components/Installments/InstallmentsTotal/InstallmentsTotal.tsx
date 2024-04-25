import React from "react";
import {FormattedMessage, FormattedNumber} from "react-intl";
import "./InstallmentsTotal.css";

type Props = {
    totalAmount: number;
    customerFees: number;
};

export const InstallmentsTotal: React.FC<Props> = ({totalAmount, customerFees}) => {
        const totalAmountIncludingFees = (totalAmount / 100) + (customerFees / 100)

        return (
            <div className={"total"}>
                <div>
                    <FormattedMessage id="installments.total" defaultMessage="Total TTC"/>
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
