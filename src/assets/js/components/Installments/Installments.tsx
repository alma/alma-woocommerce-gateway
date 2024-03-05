import { CardTemplate } from "@alma/react-components";
import React from "react";
import { InstallmentsContent } from "./InstallmentsContent";
import { FeePlan } from "../alma-blocks-component";

type InstallmentsProps = {
  feePlan: FeePlan;
  amountInCents: number;
};

export const Installments: React.FC<InstallmentsProps> = ({feePlan, amountInCents}) => {
  return (
    <CardTemplate data-testid="cardInstallments" padding="none" header={null}>
      <InstallmentsContent feePlan={feePlan} amountInCents={amountInCents}/>
    </CardTemplate>
  );
};
