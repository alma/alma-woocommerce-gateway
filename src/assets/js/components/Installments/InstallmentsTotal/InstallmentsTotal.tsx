import React from "react";
import {FormattedNumber} from "react-intl";
import "./InstallmentsTotal.css";
import { __ } from '@wordpress/i18n';

type Props = {
    totalAmount: number;
    customerFees: number;
};

export const InstallmentsTotal: React.FC<Props> = ({totalAmount, customerFees}) => {
        const totalAmountIncludingFees = (totalAmount / 100) + (customerFees / 100)

        return (
            <div className={"total"}>
                <div>
                    {__( 'Total incl. VAT', 'alma-gateway-for-woocommerce' )}
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
