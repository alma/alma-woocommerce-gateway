import React from "react";
import { FormattedDate, FormattedMessage, FormattedNumber } from "react-intl";
import classNames from "classnames";

type Props = {
  installment: any;
  totalAmountInEuros: number;
};

export const Installment: React.FC<Props> = ({
  installment: { due_date, localized_due_date }, totalAmountInEuros
}: Props) => {
  const date = new Date(due_date * 1000);
  const isToday = localized_due_date === "today";

  return (
    <div
      className={classNames("installmentContent", {
        firstInstallmentContent: isToday,
      })}
    >
      <div className={classNames("bullet", { firstBullet: isToday })} />
      <div className={"installment"} data-testid="installment">
        {isToday ? (
          <FormattedMessage id="installments.today" defaultMessage="Today" />
        ) : (
          <FormattedDate
            value={date}
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
