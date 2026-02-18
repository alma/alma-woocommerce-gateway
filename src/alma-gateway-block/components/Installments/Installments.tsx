import {CardTemplate} from "@alma/react-components";
import * as React from "react";
import {InstallmentsContent} from "./InstallmentsContent";
import {FeePlan} from "../alma-block-component";

type InstallmentsProps = {
    feePlan: FeePlan;
    amount: number;
};

export const Installments: React.FC<InstallmentsProps> = ({feePlan, amount}) => {
    return (
            <CardTemplate data-testid="cardInstallments" padding={"sm"} header={null}>
                <InstallmentsContent feePlan={feePlan} amount={amount}/>
            </CardTemplate>
    );
};
