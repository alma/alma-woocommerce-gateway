import React from "react";
import { Installment } from "./Installment";
import { CardFooter } from "@alma/react-components";
import { InstallmentsTotal } from "./InstallmentsTotal/InstallmentsTotal";
import { InstallmentsTotalFees } from "./InstallmentsTotal/InstallmentsTotalFees";
import { FeePlan, PaymentPlan } from "../alma-blocks-component";
import "../Installments/Installments.css";

type InstallmentsContentProps = {
  feePlan: FeePlan;
};
export const InstallmentsContent: React.FC<InstallmentsContentProps> = ({
  feePlan,
}) => {
  if (!feePlan.paymentPlan) {
    return null;
  }
  return (
    <>
      <div className={"separator"} />
      <div className={"installments"}>
        {feePlan.paymentPlan.map((installment: PaymentPlan) => (
          <Installment
            key={`${installment.due_date}-${installment.customer_fee}-${installment.purchase_amount}`}
            installment={installment}
          />
        ))}
      </div>
      <div className={"footerCard"}>
        <CardFooter className={"footer"}>
          <InstallmentsTotal
            totalAmount={feePlan.paymentPlan[0].total_amount}
          />
          <InstallmentsTotalFees
            customerFees={feePlan.paymentPlan[0].customer_fee}
          />
        </CardFooter>
      </div>
    </>
  );
};
