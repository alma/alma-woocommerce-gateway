import * as React from "react";
import {FormattedMessage, FormattedNumber} from "react-intl";
import classNames from "classnames";

type Props = {
    installment: any;
    totalAmountInEuros: number;
    firstInstallment: boolean;
};

export const Installment: React.FC<Props> = ({
                                                 installment: {localized_due_date}, totalAmountInEuros, firstInstallment
                                             }: Props) => {

    return (
            <div
                    className={classNames("alma-installment-installmentContent", {
                        "alma-installment-firstInstallmentContent": firstInstallment,
                    })}
            >
                <div className={classNames("alma-installment-bullet", {"alma-installment-firstBullet": firstInstallment})}/>
                <div className={"alma-installment-installment"} data-testid="installment">
                    {firstInstallment ? (
                            <FormattedMessage id="installments.today"
                                              defaultMessage={localized_due_date.charAt(0).toUpperCase() + localized_due_date.slice(1)}/>
                    ) : (
                            <div>{localized_due_date}</div>
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
