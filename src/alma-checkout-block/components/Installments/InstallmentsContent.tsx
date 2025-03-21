import React from "react";
import {Installment} from "./Installment";
import {CardFooter} from "@alma/react-components";
import {InstallmentsTotal} from "./InstallmentsTotal/InstallmentsTotal";
import {InstallmentsTotalFees} from "./InstallmentsTotal/InstallmentsTotalFees";
import {FeePlan, PaymentPlan} from "../alma-blocks-component";
import "./Installments.css";

type InstallmentsContentProps = {
    feePlan: FeePlan;
    amount: number;
};
export const InstallmentsContent: React.FC<InstallmentsContentProps> = ({
                                                                            feePlan, amount
                                                                        }) => {
    if (!feePlan.paymentPlan) {
        return null;
    }
    const customerFees = feePlan.paymentPlan[0].customer_fee

    const centsToEuros = (cents: number) => {
        return cents / 100
    }

    return (
            <>
                <div className={"separator"}/>
                <div className={"installments"}>
                    {feePlan.paymentPlan.map((installment: PaymentPlan, index: number) => (
                            <Installment
                                    key={index}
                                    installment={installment}
                                    totalAmountInEuros={centsToEuros(installment.total_amount)}
                                    firstInstallment={0 === index}
                            />
                    ))}
                </div>
                <div className={"footerCard"}>
                    <CardFooter className={"footer"}>
                        <InstallmentsTotal
                                totalAmount={amount}
                                customerFees={customerFees}
                        />
                        <InstallmentsTotalFees
                                customerFees={customerFees}
                        />
                    </CardFooter>
                </div>
            </>
    );
};
