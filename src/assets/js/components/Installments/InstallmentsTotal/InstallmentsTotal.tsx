import React from "react";
import { FormattedMessage, FormattedNumber } from "react-intl";
import "./InstallmentsTotal.css";

type Props = {
  totalAmount: number;
};

export const InstallmentsTotal: React.FC<Props> = ({ totalAmount }) => (
  <div className={"total"}>
    <div>
      <FormattedMessage id="installments.total" defaultMessage="Total TTC" />
    </div>
    <div>
      <FormattedNumber
        value={totalAmount / 100}
        style="currency"
        currency="EUR"
      />
    </div>
  </div>
);