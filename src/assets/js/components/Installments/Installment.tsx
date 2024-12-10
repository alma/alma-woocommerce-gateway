import React from "react";
import { FormattedDate, FormattedNumber } from "react-intl";
import classNames from "classnames";
import {__} from "@wordpress/i18n";

type Props = {
  installment: any;
  totalAmountInEuros: number;
};

export const Installment: React.FC<Props> = ({
  installment: { due_date }, totalAmountInEuros
}: Props) => {
  const date = new Date(due_date * 1000);
  const dateToday = new Date();
  const dateTodayWithoutHour = new Intl.DateTimeFormat().format(dateToday);
  const dateWithoutHour = new Intl.DateTimeFormat().format(date);

  const isToday = dateWithoutHour === dateTodayWithoutHour;

  return (
    <div
      className={classNames("installmentContent", {
        firstInstallmentContent: isToday,
      })}
    >
      <div className={classNames("bullet", { firstBullet: isToday })} />
      <div className={"installment"} data-testid="installment">
        {isToday ? (
            __( 'Today', 'alma-gateway-for-woocommerce' )
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
