import React from "react";
import "./InstallmentsTotal.css";

type Props = {
  customerFees: number;
};

export const InstallmentsTotalFees: React.FC<Props> = ({ customerFees }) => (
  <div className={"fees"}>
    <div>Payment costs</div>
    <div className={"feesNumbers"}>{customerFees} € </div>
  </div>
);
