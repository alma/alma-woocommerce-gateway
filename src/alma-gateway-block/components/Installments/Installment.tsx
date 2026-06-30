import * as React from "react";
import {FormattedDate, FormattedNumber} from "react-intl";
import classNames from "classnames";
import {__} from "@wordpress/i18n";

type Props = {
    installment: any;
    totalAmountInEuros: number;
    firstInstallment: boolean;
};

export const Installment: React.FC<Props> = ({
                                                 installment: {due_date}, totalAmountInEuros, firstInstallment
                                             }: Props) => {

    const dueDate = new Date(due_date * 1000);

    return (
            <div
                    className={classNames("alma-installment-installmentContent", {
                        "alma-installment-firstInstallmentContent": firstInstallment,
                    })}
            >
                <div className={classNames("alma-installment-bullet", {"alma-installment-firstBullet": firstInstallment})}/>
                <div className={"alma-installment-installment"} data-testid="installment">
                    {firstInstallment ? (
                            <div>{__('Today', 'alma-gateway-for-woocommerce')}</div>
                    ) : (
                            <div>
                                <FormattedDate
                                        value={dueDate}
                                        day="numeric"
                                        month="long"
                                        year="numeric"
                                />
                            </div>
                    )}
                    <div>
                        <FormattedNumber
                                value={totalAmountInEuros}
                                style="currency"
                                currency="EUR"
                        />
                    </div>
                </div>
            </div>
    );
};
