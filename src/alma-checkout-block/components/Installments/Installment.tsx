import React from "react";
import { FormattedDate, FormattedMessage, FormattedNumber } from "react-intl";
import classNames from "classnames";

type Props = {
  installment: any;
  totalAmountInEuros: number;
  firstInstallment: boolean;
};

export const Installment: React.FC<Props> = ({
  installment: { localized_due_date }, totalAmountInEuros, firstInstallment
}: Props) => {

  return (
    <div
      className={classNames("installmentContent", {
        firstInstallmentContent: firstInstallment,
      })}
    >
      <div className={classNames("bullet", { firstBullet: firstInstallment })} />
      <div className={"installment"} data-testid="installment">
        {firstInstallment ? (
          <FormattedMessage id="installments.today" defaultMessage={localized_due_date.charAt(0).toUpperCase() + localized_due_date.slice(1)} />
        ) : (
          <FormattedDate
            value={localized_due_date}
            day="numeric"
            month="long"
            year="numeric"
          />
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
