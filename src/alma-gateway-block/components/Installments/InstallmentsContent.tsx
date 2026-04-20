import * as React from "react";
import {Installment} from "./Installment";
import {CardFooter} from "@alma/react-components";
import {InstallmentsTotal} from "./InstallmentsTotal/InstallmentsTotal";
import {InstallmentsTotalFees} from "./InstallmentsTotal/InstallmentsTotalFees";
import {FeePlan, PaymentPlan} from "../alma-block-component";
import "./Installments.css";

type InstallmentsContentProps = {
    feePlan: FeePlan;
    amount: number;
};
export const InstallmentsContent: React.FC<InstallmentsContentProps> = ({
                                                                            feePlan, amount
                                                                        }) => {
    if (!feePlan.paymentPlan || feePlan.paymentPlan.length === 0) {
        return null;
    }
    const customerFees = feePlan.paymentPlan[0].customer_fee

    const centsToEuros = (cents: number) => {
        return cents / 100
    }

    return (
            <>
                <div className={"alma-separator"}/>
                <div className={"alma-installment-installments"}>
                    {feePlan.paymentPlan.map((installment: PaymentPlan, index: number) => (
                            <Installment
                                    key={index}
                                    installment={installment}
                                    totalAmountInEuros={centsToEuros(installment.total_amount)}
                                    firstInstallment={0 === index}
                            />
                    ))}
                </div>
                <div className={"alma-installment-footerCard"}>
                    <CardFooter className={"alma-installment-footer"}>
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
