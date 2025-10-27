import React from "react";
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
                    className={classNames("installmentContent", {
                        firstInstallmentContent: firstInstallment,
                    })}
            >
                <div className={classNames("bullet", {firstBullet: firstInstallment})}/>
                <div className={"installment"} data-testid="installment">
                    {firstInstallment ? (
                            <FormattedMessage
                                    id="installments.today"
                                    defaultMessage="Aujourd'hui"
                            />
                    ) : (
                            <span>
                                {localized_due_date}
                            </span>
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
